<?php

namespace Tests\Feature\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use App\Models\User;
use App\Services\Goal\GoalPayoutReceiptService;
use App\Services\Goal\Pd01Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsQueueTest extends TestCase
{
    use RefreshDatabase;

    private function accountsUser(GoalOrgUnit $org, array $guards): User
    {
        return User::factory()->create([
            'crm_role_type' => 14,
            'goal_org_unit_id' => $org->id,
            'crm_page_right' => json_encode(array_values($guards)),
            'status' => 1,
        ]);
    }

    private function verifiedCase(User $actor, GoalOrgUnit $org, array $branchMis = []): array
    {
        $case = GoalLoanCase::factory()->sanctioned()->create([
            'goal_org_unit_id' => $org->id,
            'owner_id' => $actor->id,
            'sanction_amount' => 500000,
            'branch_mis' => $branchMis ?: null,
            'lender_name' => 'HDFC Bank',
        ]);
        $svc = app(Pd01Service::class);
        $pending = $svc->recordPending($case, [
            'amount' => 100000,
            'disbursed_on' => now()->toDateString(),
            'bank_reference' => 'REF-ACC-'.uniqid(),
        ], $actor);
        $svc->verify($pending, $actor);

        $item = GoalAccountsItem::query()->where('goal_loan_case_id', $case->id)->firstOrFail();
        $payout = GoalPayout::query()->where('goal_loan_case_id', $case->id)->firstOrFail();

        return compact('case', 'item', 'payout', 'svc');
    }

    public function test_expected_payout_prefers_branch_mis_amount(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $actor = User::factory()->create(['goal_org_unit_id' => $org->id]);
        // Branch MIS recalculates payout_amount = disb × payout_pct/100 (100000 × 80% = 80000).
        $data = $this->verifiedCase($actor, $org, [
            'payout_pct' => 80,
        ]);

        $this->assertEquals(80000.0, (float) $data['payout']->expected_amount);
    }

    public function test_partial_receipts_append_and_compute_outstanding(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $actor = $this->accountsUser($org, [
            'accounts_queue_guard', 'accounts_approve_guard', 'record_receipt_guard', 'payout_processing_guard',
        ]);
        $data = $this->verifiedCase($actor, $org, ['payout_pct' => 80]);
        $payout = $data['payout'];
        $item = $data['item'];
        $this->assertEquals(80000.0, (float) $payout->expected_amount);

        GoalPmsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)
            ->update(['status' => 'Verified', 'verified_at' => now()]);
        $item->update([
            'status' => 'Approved',
            'report_number' => 'RPT-80',
            'approved_at' => now(),
            'approved_by' => $actor->id,
        ]);
        $data['svc']->refreshPayoutEligibility($data['case']->fresh());
        $payout = $payout->fresh();
        $this->assertSame('Eligible', $payout->status);

        $receipts = app(GoalPayoutReceiptService::class);
        $receipts->record($payout, [
            'amount' => 25000,
            'received_on' => now()->toDateString(),
            'reference' => 'R1',
            'difference_reason' => 'Partial first tranche',
        ], $actor);
        $receipts->record($payout->fresh(), [
            'amount' => 15000,
            'received_on' => now()->toDateString(),
            'reference' => 'R2',
            'difference_reason' => 'Partial second tranche',
        ], $actor);

        $payout = $payout->fresh(['receipts', 'adjustments']);
        $this->assertSame(2, $payout->receipts()->count());
        $this->assertEquals(40000.0, (float) $payout->received_amount);
        $this->assertEquals(40000.0, $payout->outstandingAmount());
        $this->assertSame('Processing', $payout->status);
    }

    public function test_approve_requires_accounts_approve_guard(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->accountsUser($org, ['accounts_queue_guard', 'record_receipt_guard']);
        $data = $this->verifiedCase($user, $org);
        $item = $data['item'];
        $item->update(['report_number' => 'RPT-X', 'status' => 'Report Entered']);

        $this->actingAs($user)
            ->post(route('goal.accounts.approve', $item))
            ->assertForbidden();
    }

    public function test_approve_with_guard_unlocks_eligibility_with_pms(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->accountsUser($org, [
            'accounts_queue_guard', 'accounts_approve_guard', 'record_receipt_guard',
        ]);
        $data = $this->verifiedCase($user, $org, ['payout_pct' => 5]);
        $item = $data['item'];
        $payout = $data['payout'];

        GoalPmsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)
            ->update(['status' => 'Verified', 'verified_at' => now()]);
        $item->update(['report_number' => 'RPT-OK', 'status' => 'Report Entered']);

        $this->actingAs($user)
            ->post(route('goal.accounts.approve', $item))
            ->assertRedirect();

        $this->assertSame('Approved', $item->fresh()->status);
        $this->assertSame('Eligible', $payout->fresh()->status);
    }

    public function test_queue_ajax_partial_returns_html(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->accountsUser($org, ['accounts_queue_guard', 'accounts_approve_guard']);
        $this->verifiedCase($user, $org);

        $this->actingAs($user)
            ->get(route('goal.accounts.index', ['partial' => 1]), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertSee('Accounts Queue', false);
    }
}
