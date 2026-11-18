<?php

namespace Tests\Feature\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalCaseEvent;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use App\Models\GoalRevenueEntry;
use App\Models\GoalTarget;
use App\Models\User;
use App\Services\Goal\Pd01Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Pd01ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_pending_is_idempotent_by_key(): void
    {
        $actor = User::factory()->create();
        $case = GoalLoanCase::factory()->sanctioned()->create([
            'sanction_amount' => 500000,
        ]);
        $svc = app(Pd01Service::class);

        $payload = [
            'amount' => 100000,
            'disbursed_on' => now()->toDateString(),
            'bank_reference' => 'REF-1',
            'idempotency_key' => 'test-key-1',
        ];

        $a = $svc->recordPending($case, $payload, $actor);
        $b = $svc->recordPending($case, $payload, $actor);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, $case->disbursements()->count());
    }

    public function test_verify_creates_revenue_queues_and_audit(): void
    {
        $actor = User::factory()->create();
        $org = GoalOrgUnit::factory()->create();
        $case = GoalLoanCase::factory()->sanctioned()->create([
            'goal_org_unit_id' => $org->id,
            'owner_id' => $actor->id,
            'sanction_amount' => 500000,
        ]);
        GoalTarget::factory()->approved()->create([
            'period' => now()->format('Y-m'),
            'metric' => 'revenue',
            'goal_org_unit_id' => $org->id,
            'user_id' => null,
            'base_target' => 10000,
            'actual' => 0,
            'closing_shortfall' => 10000,
        ]);

        $svc = app(Pd01Service::class);
        $pending = $svc->recordPending($case, [
            'amount' => 100000,
            'disbursed_on' => now()->toDateString(),
            'bank_reference' => 'REF-VERIFY',
        ], $actor);

        $verified = $svc->verify($pending, $actor);

        $this->assertNotNull($verified->verified_at);
        $this->assertSame('Disbursed', $case->fresh()->status);
        $this->assertSame(1, GoalRevenueEntry::query()->where('goal_loan_case_id', $case->id)->count());
        $this->assertSame(1, GoalPmsItem::query()->where('goal_loan_case_id', $case->id)->count());
        $this->assertSame(1, GoalAccountsItem::query()->where('goal_loan_case_id', $case->id)->count());
        $this->assertSame('On Hold', GoalPayout::query()->where('goal_loan_case_id', $case->id)->value('status'));
        $this->assertTrue(
            GoalCaseEvent::query()
                ->where('goal_loan_case_id', $case->id)
                ->where('event_type', 'disbursement')
                ->exists()
        );
    }

    public function test_payout_becomes_eligible_after_pms_and_accounts_approve(): void
    {
        $actor = User::factory()->create();
        $case = GoalLoanCase::factory()->sanctioned()->create(['sanction_amount' => 500000]);
        $svc = app(Pd01Service::class);
        $pending = $svc->recordPending($case, [
            'amount' => 100000,
            'disbursed_on' => now()->toDateString(),
            'bank_reference' => 'REF-ELIG',
        ], $actor);
        $svc->verify($pending, $actor);

        $payout = GoalPayout::query()->where('goal_loan_case_id', $case->id)->firstOrFail();
        $this->assertSame('On Hold', $payout->status);

        GoalPmsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)
            ->update(['status' => 'Verified']);
        GoalAccountsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)
            ->update([
                'status' => 'Approved',
                'report_number' => 'RPT-1',
                'approved_at' => now(),
            ]);

        $svc->refreshPayoutEligibility($case->fresh());
        $this->assertSame('Eligible', $payout->fresh()->status);
    }
}
