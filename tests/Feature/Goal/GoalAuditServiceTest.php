<?php

namespace Tests\Feature\Goal;

use App\Models\GoalCaseEvent;
use App\Models\GoalDisbursement;
use App\Models\GoalLoanCase;
use App\Models\GoalLoanLead;
use App\Models\GoalLoanLeadEvent;
use App\Models\User;
use App\Services\Goal\GoalAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_case_and_lead_events(): void
    {
        $actor = User::factory()->create();
        $lead = GoalLoanLead::factory()->create();
        $case = GoalLoanCase::factory()->create();

        GoalAuditService::recordLead($lead, GoalAuditService::TYPE_ALLOCATION, 'Allocated', 'to SM', [
            'user_id' => $actor->id,
        ]);
        GoalAuditService::recordCase($case, GoalAuditService::TYPE_STATUS, 'Status Changed', $actor, [
            'from' => 'Draft',
            'to' => 'Ready for Login',
        ]);

        $this->assertTrue(
            GoalLoanLeadEvent::query()->where('goal_loan_lead_id', $lead->id)->where('event_type', 'allocation')->exists()
        );
        $this->assertTrue(
            GoalCaseEvent::query()->where('goal_loan_case_id', $case->id)->where('event_type', 'status_change')->exists()
        );
    }

    public function test_financial_event_links_related_model(): void
    {
        $actor = User::factory()->create();
        $case = GoalLoanCase::factory()->create();
        $disb = GoalDisbursement::query()->create([
            'goal_loan_case_id' => $case->id,
            'amount' => 100000,
            'disbursed_on' => now()->toDateString(),
            'bank_reference' => 'AUD-1',
            'status' => 'Pending Verification',
            'created_by' => $actor->id,
        ]);

        GoalAuditService::recordFinancial(
            $case,
            GoalAuditService::TYPE_DISBURSEMENT,
            'Disbursement recorded',
            $disb,
            $actor,
            ['to' => (string) $disb->amount]
        );

        $event = GoalCaseEvent::query()
            ->where('goal_loan_case_id', $case->id)
            ->where('event_type', 'disbursement')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame(GoalDisbursement::class, $event->related_type);
        $this->assertSame($disb->id, (int) $event->related_id);
    }
}
