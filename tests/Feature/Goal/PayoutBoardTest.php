<?php

namespace Tests\Feature\Goal;

use App\Models\GoalAccountsItem;
use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\GoalPayout;
use App\Models\GoalPmsItem;
use App\Models\User;
use App\Services\Goal\Pd01Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutBoardTest extends TestCase
{
    use RefreshDatabase;

    private function payoutUser(GoalOrgUnit $org, array $guards): User
    {
        return User::factory()->create([
            'crm_role_type' => 14,
            'goal_org_unit_id' => $org->id,
            'crm_page_right' => json_encode(array_values($guards)),
            'status' => 1,
        ]);
    }

    private function verifiedCase(User $actor, GoalOrgUnit $org): array
    {
        $case = GoalLoanCase::factory()->sanctioned()->create([
            'goal_org_unit_id' => $org->id,
            'owner_id' => $actor->id,
            'sanction_amount' => 500000,
            'lender_name' => 'HDFC Bank',
        ]);
        $svc = app(Pd01Service::class);
        $pending = $svc->recordPending($case, [
            'amount' => 100000,
            'disbursed_on' => now()->toDateString(),
            'bank_reference' => 'REF-PAY-'.uniqid(),
        ], $actor);
        $svc->verify($pending, $actor);

        $item = GoalAccountsItem::query()->where('goal_loan_case_id', $case->id)->firstOrFail();
        $payout = GoalPayout::query()->where('goal_loan_case_id', $case->id)->firstOrFail();

        return compact('case', 'item', 'payout', 'svc');
    }

    public function test_index_renders_board_and_kpis(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['payout_processing_guard', 'accounts_approve_guard', 'record_receipt_guard']);
        $this->verifiedCase($user, $org);

        $this->actingAs($user)
            ->get(route('goal.payouts.index'))
            ->assertOk()
            ->assertSee('Payout Management')
            ->assertSee('Payout Register')
            ->assertSee('On Hold');
    }

    public function test_partial_ajax_returns_board_fragment(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['payout_processing_guard']);
        $this->verifiedCase($user, $org);

        $this->actingAs($user)
            ->get(route('goal.payouts.index', ['partial' => 1]))
            ->assertOk()
            ->assertSee('pay-layout');
    }

    public function test_kpi_tab_filters_register(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['payout_processing_guard']);
        $this->verifiedCase($user, $org);

        $this->actingAs($user)
            ->get(route('goal.payouts.index', ['tab' => 'eligible', 'partial' => 1]))
            ->assertOk()
            ->assertSee('Showing 0 records');
    }

    public function test_release_hold_blocked_until_confirmations_complete(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['payout_processing_guard', 'accounts_approve_guard']);
        $data = $this->verifiedCase($user, $org);
        $payout = $data['payout'];

        $this->actingAs($user)
            ->post(route('goal.payouts.release-hold', $payout), ['confirm' => '1'])
            ->assertSessionHasErrors();

        $this->assertSame('On Hold', $payout->fresh()->status);
    }

    public function test_release_hold_succeeds_when_gates_pass(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['payout_processing_guard', 'accounts_approve_guard']);
        $data = $this->verifiedCase($user, $org);
        $payout = $data['payout'];
        $item = $data['item'];

        GoalPmsItem::query()->where('goal_disbursement_id', $payout->goal_disbursement_id)
            ->update(['status' => 'Verified', 'verified_at' => now()]);
        $item->update([
            'status' => 'Approved',
            'report_number' => 'RB/HD/TEST',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('goal.payouts.release-hold', $payout), ['confirm' => '1'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Eligible', $payout->fresh()->status);
    }

    public function test_show_page_loads(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['payout_processing_guard']);
        $data = $this->verifiedCase($user, $org);

        $this->actingAs($user)
            ->get(route('goal.payouts.show', $data['payout']))
            ->assertOk()
            ->assertSee('Payout Details')
            ->assertSee('Receipt History');
    }

    public function test_index_requires_payout_guard(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $user = $this->payoutUser($org, ['accounts_queue_guard']);

        $this->actingAs($user)
            ->get(route('goal.payouts.index'))
            ->assertForbidden();
    }
}
