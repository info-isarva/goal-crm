<?php

namespace Tests\Feature\Goal;

use App\Models\User;
use App\Services\Goal\GoalSetTargetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Permission rule: user may edit only employees below them in their reporting line.
 * Never self, manager, peer, or another region's tree.
 */
class GoalSetTargetsPermissionTest extends TestCase
{
    use RefreshDatabase;

    private GoalSetTargetsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GoalSetTargetsService::class);
    }

    /**
     * CEO → RD North / RD South → Cluster under North → BM → staff
     *
     * @return array{ceo: User, rdNorth: User, rdSouth: User, cluster: User, bm: User, staff: User, peerStaff: User}
     */
    private function hierarchy(): array
    {
        $ceo = User::factory()->create([
            'name' => 'CEO',
            'crm_role_type' => 5,
            'job_role' => 'ceo',
            'reports_to_user_id' => null,
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $rdNorth = User::factory()->create([
            'name' => 'RD North',
            'crm_role_type' => 6,
            'job_role' => 'rd',
            'reports_to_user_id' => $ceo->id,
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $rdSouth = User::factory()->create([
            'name' => 'RD South',
            'crm_role_type' => 6,
            'job_role' => 'rd',
            'reports_to_user_id' => $ceo->id,
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $cluster = User::factory()->create([
            'name' => 'Cluster A',
            'crm_role_type' => 7,
            'job_role' => 'cluster',
            'reports_to_user_id' => $rdNorth->id,
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $bm = User::factory()->create([
            'name' => 'Branch Manager',
            'crm_role_type' => 8,
            'job_role' => 'bm',
            'reports_to_user_id' => $cluster->id,
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $staff = User::factory()->create([
            'name' => 'Sales Manager',
            'crm_role_type' => 9,
            'job_role' => 'sm',
            'reports_to_user_id' => $bm->id,
            'crm_page_right' => json_encode([]),
            'status' => 1,
        ]);

        $peerStaff = User::factory()->create([
            'name' => 'Junior Sales',
            'crm_role_type' => 9,
            'job_role' => 'jsm',
            'reports_to_user_id' => $bm->id,
            'crm_page_right' => json_encode([]),
            'status' => 1,
        ]);

        return compact('ceo', 'rdNorth', 'rdSouth', 'cluster', 'bm', 'staff', 'peerStaff');
    }

    public function test_user_cannot_edit_own_target(): void
    {
        $h = $this->hierarchy();

        $this->expectException(ValidationException::class);
        $this->service->assertCanEdit($h['rdNorth'], (int) $h['rdNorth']->id);
    }

    public function test_user_cannot_edit_manager_above_them(): void
    {
        $h = $this->hierarchy();

        $this->expectException(ValidationException::class);
        $this->service->assertCanEdit($h['rdNorth'], (int) $h['ceo']->id);
    }

    public function test_user_cannot_edit_peer_in_same_level(): void
    {
        $h = $this->hierarchy();

        $this->expectException(ValidationException::class);
        $this->service->assertCanEdit($h['rdNorth'], (int) $h['rdSouth']->id);
    }

    public function test_user_cannot_edit_someone_in_another_region(): void
    {
        $h = $this->hierarchy();
        $southCluster = User::factory()->create([
            'name' => 'South Cluster',
            'crm_role_type' => 7,
            'job_role' => 'cluster',
            'reports_to_user_id' => $h['rdSouth']->id,
            'status' => 1,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->assertCanEdit($h['rdNorth'], (int) $southCluster->id);
    }

    public function test_branch_manager_can_edit_own_staff_only(): void
    {
        $h = $this->hierarchy();

        $this->service->assertCanEdit($h['bm'], (int) $h['staff']->id);
        $this->service->assertCanEdit($h['bm'], (int) $h['peerStaff']->id);

        $this->expectException(ValidationException::class);
        $this->service->assertCanEdit($h['bm'], (int) $h['cluster']->id);
    }

    public function test_save_draft_api_rejects_out_of_tree_employee(): void
    {
        $h = $this->hierarchy();
        $period = now()->addMonthNoOverflow()->format('Y-m');

        $this->actingAs($h['rdNorth'])
            ->postJson(route('goal.targets.save-draft'), [
                'period' => $period,
                'metric' => 'revenue',
                'rows' => [
                    [
                        'employee_id' => $h['rdSouth']->id,
                        'set_target' => 100,
                        'owner_target' => 0,
                        'row_source' => 'edited',
                    ],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_save_draft_api_allows_subtree_employee(): void
    {
        $h = $this->hierarchy();
        $period = now()->addMonthNoOverflow()->format('Y-m');

        $this->actingAs($h['rdNorth'])
            ->postJson(route('goal.targets.save-draft'), [
                'period' => $period,
                'metric' => 'revenue',
                'rows' => [
                    [
                        'employee_id' => $h['cluster']->id,
                        'set_target' => 120,
                        'owner_target' => 10,
                        'row_source' => 'edited',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_set_targets_page_renders(): void
    {
        $h = $this->hierarchy();

        $this->actingAs($h['rdNorth'])
            ->get(route('goal.targets.set'))
            ->assertOk()
            ->assertSee('Set Targets')
            ->assertSee('Target setup');
    }

    public function test_super_admin_cannot_set_target_on_ceo(): void
    {
        $h = $this->hierarchy();
        $sa = User::factory()->create([
            'name' => 'Super Admin',
            'crm_role_type' => 0,
            'job_role' => 'admin',
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->assertCanEdit($sa, (int) $h['ceo']->id);
    }

    public function test_super_admin_tree_starts_at_regional_directors(): void
    {
        $h = $this->hierarchy();
        $sa = User::factory()->create([
            'name' => 'Super Admin',
            'crm_role_type' => 0,
            'job_role' => 'admin',
            'crm_page_right' => json_encode(['manage_targets_guard']),
            'status' => 1,
        ]);

        $payload = $this->service->tree($sa, now()->addMonthNoOverflow()->format('Y-m'), 'revenue');

        $this->assertFalse($payload['self']['receives_target']);
        $rootIds = collect($payload['tree'])->pluck('id')->all();
        $this->assertContains((int) $h['rdNorth']->id, $rootIds);
        $this->assertContains((int) $h['rdSouth']->id, $rootIds);
        $this->assertNotContains((int) $h['ceo']->id, $rootIds);
    }

    public function test_rd_sees_remainder_to_distribute(): void
    {
        $h = $this->hierarchy();
        $period = now()->addMonthNoOverflow()->format('Y-m');

        \App\Models\GoalTarget::create([
            'period' => $period,
            'metric' => 'revenue',
            'user_id' => $h['rdNorth']->id,
            'goal_org_unit_id' => null,
            'base_target' => 10000,
            'owner_target' => 5000,
            'status' => 'Draft',
        ]);

        $payload = $this->service->tree($h['rdNorth'], $period, 'revenue');

        $this->assertTrue($payload['self']['receives_target']);
        $this->assertEquals(10000.0, $payload['self']['set_target']);
        $this->assertEquals(5000.0, $payload['self']['owner_target']);
        $this->assertEquals(5000.0, $payload['self']['remainder']);
    }
}
