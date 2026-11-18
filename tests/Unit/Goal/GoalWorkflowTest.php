<?php

namespace Tests\Unit\Goal;

use App\Services\Goal\GoalWorkflow;
use PHPUnit\Framework\TestCase;

class GoalWorkflowTest extends TestCase
{
    public function test_illegal_lead_transition_is_rejected(): void
    {
        $gate = GoalWorkflow::validateLead('New', 'Disbursed');
        $this->assertFalse($gate['ok']);
        $this->assertNotEmpty($gate['errors']);
    }

    public function test_assign_requires_assignee(): void
    {
        $gate = GoalWorkflow::validateLead('Pending BM Allocation', 'Assigned', []);
        $this->assertFalse($gate['ok']);
        $this->assertStringContainsString('JSM', implode(' ', $gate['errors']));
    }

    public function test_assign_with_assignee_passes(): void
    {
        $gate = GoalWorkflow::validateLead('Pending BM Allocation', 'Assigned', ['assignee' => 12]);
        $this->assertTrue($gate['ok']);
    }

    public function test_application_disbursed_requires_verified_disbursement(): void
    {
        $gate = GoalWorkflow::validateApplication('Sanctioned', 'Disbursed', []);
        $this->assertFalse($gate['ok']);

        $ok = GoalWorkflow::validateApplication('Sanctioned', 'Disbursed', ['verifiedDisbursement' => true]);
        $this->assertTrue($ok['ok']);
    }

    public function test_pd01_requires_amount_date_ref(): void
    {
        $gate = GoalWorkflow::validatePd01(['from' => 'Sanctioned']);
        $this->assertFalse($gate['ok']);
        $this->assertGreaterThanOrEqual(3, count($gate['errors']));
    }

    public function test_workspace_locks_after_completion_statuses(): void
    {
        foreach (['Disbursed', 'PMS', 'Rejected', 'Closed'] as $status) {
            $this->assertTrue(GoalWorkflow::isWorkspaceLocked($status), $status);
        }
        foreach (['Draft', 'Ready for Login', 'Bank Login', 'Processing', 'Sanctioned'] as $status) {
            $this->assertFalse(GoalWorkflow::isWorkspaceLocked($status), $status);
        }
    }
}
