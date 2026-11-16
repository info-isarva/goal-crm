<?php

namespace Database\Seeders;

use App\Models\GoalOrgUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GoalHierarchyUsersSeeder extends Seeder
{
    /**
     * Goal Loan hierarchy only (no Admin / Manager / Employee):
     * MD → CEO → RD → Cluster Head → BM → VRM / Sales (JSM·SM·SSM)
     * Functional: PMS, Accounts
     */
    public const GOAL_ROLE_IDS = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14];

    public function run(): void
    {
        $password = Hash::make('password');

        // Remove previously seeded non-Goal sample employees
        User::query()->whereIn('email', [
            'emp.halasuru@isarva.test',
            'emp.mysore@isarva.test',
        ])->delete();

        // --- Org tree ---
        $south = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'SOUTH'],
            ['type' => GoalOrgUnit::TYPE_REGION, 'parent_id' => null, 'name' => 'South Region', 'is_active' => true]
        );

        $blr = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'BLR-CLU'],
            ['type' => GoalOrgUnit::TYPE_CLUSTER, 'parent_id' => $south->id, 'name' => 'Bangalore Cluster', 'is_active' => true]
        );

        $halasuru = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'HAL-BR'],
            ['type' => GoalOrgUnit::TYPE_BRANCH, 'parent_id' => $blr->id, 'name' => 'Halasuru Branch', 'is_active' => true]
        );

        $indiranagar = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'IND-BR'],
            ['type' => GoalOrgUnit::TYPE_BRANCH, 'parent_id' => $blr->id, 'name' => 'Indiranagar Branch', 'is_active' => true]
        );

        $mysore = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'MYS-CLU'],
            ['type' => GoalOrgUnit::TYPE_CLUSTER, 'parent_id' => $south->id, 'name' => 'Mysore Cluster', 'is_active' => true]
        );

        $mysoreMain = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'MYS-BR'],
            ['type' => GoalOrgUnit::TYPE_BRANCH, 'parent_id' => $mysore->id, 'name' => 'Mysore Main Branch', 'is_active' => true]
        );

        $rightsForRole = function (int $roleId): array {
            $role = Role::query()->find($roleId);
            if (!$role) {
                return [];
            }

            return $role->permissions()->pluck('guard_name')->map(fn ($g) => (string) $g)->values()->all();
        };

        $make = function (
            string $email,
            string $name,
            int $roleId,
            string $jobRole,
            ?int $orgId,
            ?User $manager,
            float $target = 0
        ) use ($password, $rightsForRole): User {
            $pageRights = $rightsForRole($roleId);
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'status' => 1,
                    'crm_role_type' => $roleId,
                    'job_role' => $jobRole,
                    'goal_org_unit_id' => $orgId,
                    'reports_to_user_id' => $manager?->id,
                    'sales_target' => $target,
                    'crm_page_right' => json_encode($pageRights),
                    '2fa_enabled' => false,
                ]
            );
            $user->assign_manager = $manager?->id;
            $user->crm_page_right = json_encode($pageRights);
            $user->save();

            return $user->fresh();
        };

        // L1 MD → L2 CEO
        $md = $make('md@gmail.com', 'MD User', 4, 'md', $south->id, null, 500000);
        $ceo = $make('ceo@gmail.com', 'CEO', 5, 'ceo', $south->id, $md, 400000);

        // L3 Regional Director
        $rd = $make('rd.south@isarva.test', 'Priya Sharma', 6, 'rd', $south->id, $ceo, 300000);

        // L4 Cluster Heads (≈3–4 per region)
        $chBlr = $make('cluster.blr@isarva.test', 'Vikram Singh', 7, 'cluster', $blr->id, $rd, 250000);
        $chMys = $make('cluster.mys@isarva.test', 'Rajesh Kumar', 7, 'cluster', $mysore->id, $rd, 200000);
        $chHyd = $make('cluster.hyd@isarva.test', 'Ananya Reddy', 7, 'cluster', $blr->id, $rd, 180000);

        // L5 Branch Managers
        $bmHal = $make('bm.halasuru@isarva.test', 'Amit Verma', 8, 'bm', $halasuru->id, $chBlr, 150000);
        $bmInd = $make('bm.indiranagar@isarva.test', 'Suresh Patel', 8, 'bm', $indiranagar->id, $chBlr, 140000);
        $bmMys = $make('bm.mysore@isarva.test', 'Meera Joshi', 8, 'bm', $mysoreMain->id, $chMys, 120000);

        // L6A VRM Team + L6B Sales Team (JSM · SM · SSM) — report to BM
        $make('vrm.halasuru@isarva.test', 'Neha Rao', 9, 'vrm', $halasuru->id, $bmHal, 80000);
        $make('jsm.halasuru@isarva.test', 'Kavya Iyer', 10, 'jsm', $halasuru->id, $bmHal, 60000);
        $make('sm.halasuru@isarva.test', 'Rohan Das', 11, 'sm', $halasuru->id, $bmHal, 70000);
        $make('ssm.halasuru@isarva.test', 'Imran Khan', 12, 'ssm', $halasuru->id, $bmHal, 65000);

        $make('vrm.indiranagar@isarva.test', 'Anjali Mehta', 9, 'vrm', $indiranagar->id, $bmInd, 75000);
        $make('sm.indiranagar@isarva.test', 'Deepak Nair', 11, 'sm', $indiranagar->id, $bmInd, 65000);
        $make('jsm.indiranagar@isarva.test', 'Pooja Shetty', 10, 'jsm', $indiranagar->id, $bmInd, 55000);

        $make('vrm.mysore@isarva.test', 'Arjun Pillai', 9, 'vrm', $mysoreMain->id, $bmMys, 70000);
        $make('ssm.mysore@isarva.test', 'Lakshmi Devi', 12, 'ssm', $mysoreMain->id, $bmMys, 55000);

        // Functional teams (after disbursement) — report to CEO for chart placement
        $make('pms.team@isarva.test', 'Sneha Kapoor', 13, 'pms', $south->id, $ceo, 90000);
        $make('accounts.team@isarva.test', 'Ravi Menon', 14, 'accounts', $south->id, $ceo, 90000);

        $total = User::query()->whereIn('crm_role_type', self::GOAL_ROLE_IDS)->count();
        $this->command?->info("Goal hierarchy users ready: {$total} (MD→CEO→RD→CH→BM→VRM/Sales + PMS/Accounts)");
        $this->command?->info('Sample login: rd.south@isarva.test / password');
    }
}
