<?php

namespace Database\Seeders;

use App\Models\ParentPermission;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoalRolesPermissionsSeeder extends Seeder
{
    /**
     * Goal operational roles (IDs 4–14) and permission guards from the UI prototype.
     * Super Admin (0) and Admin (1) remain from SuperAdminSeeder (Manager/Employee removed).
     */
    public function run(): void
    {
        $this->seedRoles();
        $parents = $this->seedParentGroups();
        $guards = $this->seedPermissions($parents);
        $this->attachRolePermissions($guards);
    }

    protected function seedRoles(): void
    {
        $roles = [
            4 => ['name' => 'MD', 'description' => 'Managing Director — company-wide oversight'],
            5 => ['name' => 'CEO', 'description' => 'Chief Executive Officer — operational oversight'],
            6 => ['name' => 'RD', 'description' => 'Regional Director'],
            7 => ['name' => 'Cluster Head', 'description' => 'Cluster Head — multi-branch coordination'],
            8 => ['name' => 'BM', 'description' => 'Branch Manager — allocation and branch ops'],
            9 => ['name' => 'VRM', 'description' => 'Virtual Relationship Manager — telecalling'],
            10 => ['name' => 'JSM', 'description' => 'Junior Sales Manager'],
            11 => ['name' => 'SM', 'description' => 'Sales Manager'],
            12 => ['name' => 'SSM', 'description' => 'Senior Sales Manager'],
            13 => ['name' => 'PMS', 'description' => 'Portfolio Management Services'],
            14 => ['name' => 'Accounts', 'description' => 'Accounts — report number and payouts'],
        ];

        foreach ($roles as $id => $data) {
            $role = Role::withTrashed()->find($id);
            if ($role) {
                if ($role->trashed()) {
                    $role->restore();
                }
                $role->fill([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'updated_at' => now(),
                ])->save();
                continue;
            }

            $byName = Role::withTrashed()->where('name', $data['name'])->first();
            if ($byName) {
                $this->command?->warn("Role '{$data['name']}' exists as id {$byName->id}; expected {$id}.");
                continue;
            }

            DB::table('roles')->insert([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Goal roles checked (IDs 4–14).');
    }

    protected function seedParentGroups(): array
    {
        $names = [
            'Goal Dashboard',
            'Goal Leads & Calling',
            'Goal Allocation & Cases',
            'Goal PMS & Accounts',
            'Goal Masters',
            'Goal Organisation',
            'Goal MIS & Targets',
        ];

        $map = [];
        foreach ($names as $name) {
            $parent = ParentPermission::query()->firstOrCreate(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
            $map[$name] = $parent->id;
        }

        return $map;
    }

    protected function seedPermissions(array $parents): array
    {
        $defs = [
            // Dashboard (sidebar: Home group)
            ['parent' => 'Goal Dashboard', 'name' => 'Analytics Dashboard', 'guard' => 'goal_analytics_guard', 'desc' => 'Analytics dashboard'],
            ['parent' => 'Goal Dashboard', 'name' => 'MIS Reports', 'guard' => 'mis_reports_guard', 'desc' => 'MIS reports'],
            ['parent' => 'Goal Dashboard', 'name' => 'Scheduled Reports', 'guard' => 'scheduled_reports_guard', 'desc' => 'Scheduled reports'],
            // Lead Management
            ['parent' => 'Goal Leads & Calling', 'name' => 'VRM Calling', 'guard' => 'vrm_calling_guard', 'desc' => 'Access calling workspace'],
            ['parent' => 'Goal Leads & Calling', 'name' => 'Create Lead', 'guard' => 'create_lead_guard', 'desc' => 'Create VRM leads'],
            ['parent' => 'Goal Leads & Calling', 'name' => 'Channel Lead', 'guard' => 'channel_lead_guard', 'desc' => 'Create channel-sourced leads'],
            ['parent' => 'Goal Leads & Calling', 'name' => 'View Leads', 'guard' => 'view_leads_guard', 'desc' => 'Lead register access'],
            // Loan Processing
            ['parent' => 'Goal Allocation & Cases', 'name' => 'BM Allocation', 'guard' => 'bm_allocation_guard', 'desc' => 'Assign / reassign leads'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'View Loan Cases', 'guard' => 'view_loan_cases_guard', 'desc' => 'Case pipeline'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'Edit Case Workspace', 'guard' => 'edit_case_workspace_guard', 'desc' => 'Customer details, BM Reco, bank'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'Customer Visits & Follow-ups', 'guard' => 'customer_visits_guard', 'desc' => 'Visits, tasks and follow-ups'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'Documents', 'guard' => 'case_documents_guard', 'desc' => 'Case documents hub'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'Bank Processing', 'guard' => 'bank_processing_guard', 'desc' => 'Lenders and bank queries'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'Sanction & Disbursement', 'guard' => 'sanction_disbursement_guard', 'desc' => 'Sanction and disbursement hub'],
            ['parent' => 'Goal Allocation & Cases', 'name' => 'Record Disbursement', 'guard' => 'record_disbursement_guard', 'desc' => 'Mark verified disbursement'],
            // Post-Disbursement
            ['parent' => 'Goal PMS & Accounts', 'name' => 'PMS Verification', 'guard' => 'pms_verification_guard', 'desc' => 'Confirm disbursement'],
            ['parent' => 'Goal PMS & Accounts', 'name' => 'Accounts Queue', 'guard' => 'accounts_queue_guard', 'desc' => 'Enter report number'],
            ['parent' => 'Goal PMS & Accounts', 'name' => 'Accounts Approve', 'guard' => 'accounts_approve_guard', 'desc' => 'Approve accounts reconciliation'],
            ['parent' => 'Goal PMS & Accounts', 'name' => 'Payout Processing', 'guard' => 'payout_processing_guard', 'desc' => 'Payout board and status'],
            ['parent' => 'Goal PMS & Accounts', 'name' => 'Record Receipt', 'guard' => 'record_receipt_guard', 'desc' => 'Partial / full receipts'],
            // Masters
            ['parent' => 'Goal Masters', 'name' => 'Manage Products', 'guard' => 'manage_products_guard', 'desc' => 'Product master under Masters'],
            ['parent' => 'Goal Masters', 'name' => 'Manage Dispositions', 'guard' => 'manage_dispositions_guard', 'desc' => 'VRM disposition master under Masters'],
            ['parent' => 'Goal Masters', 'name' => 'Manage Sources', 'guard' => 'manage_sources_guard', 'desc' => 'Lead source master under Masters'],
            // Organization
            ['parent' => 'Goal Organisation', 'name' => 'Manage Org Units', 'guard' => 'manage_org_units_guard', 'desc' => 'Region / cluster / branch CRUD'],
            ['parent' => 'Goal Organisation', 'name' => 'Company / Branches', 'guard' => 'manage_company_guard', 'desc' => 'Company and organization master'],
            ['parent' => 'Goal Organisation', 'name' => 'Manage Users', 'guard' => 'manage_users_guard', 'desc' => 'Create and edit users'],
            ['parent' => 'Goal Organisation', 'name' => 'Manage Roles', 'guard' => 'manage_roles_guard', 'desc' => 'Role defaults'],
            ['parent' => 'Goal Organisation', 'name' => 'Manage Permissions', 'guard' => 'manage_permissions_guard', 'desc' => 'Permission catalog'],
            ['parent' => 'Goal Organisation', 'name' => 'Audit Logs', 'guard' => 'view_audit_logs_guard', 'desc' => 'Activity / audit log access'],
            // Performance & Finance
            ['parent' => 'Goal MIS & Targets', 'name' => 'View Targets', 'guard' => 'view_targets_guard', 'desc' => 'Targets and carry-forward'],
            ['parent' => 'Goal MIS & Targets', 'name' => 'Manage Targets', 'guard' => 'manage_targets_guard', 'desc' => 'Approve / adjust targets'],
            ['parent' => 'Goal MIS & Targets', 'name' => 'Branch MIS', 'guard' => 'branch_mis_guard', 'desc' => 'Branch MIS grid and export'],
            ['parent' => 'Goal MIS & Targets', 'name' => 'Revenue Ledger', 'guard' => 'revenue_ledger_guard', 'desc' => 'Revenue ledger view'],
        ];

        $guardIds = [];
        foreach ($defs as $def) {
            $permission = Permission::withTrashed()->where('guard_name', $def['guard'])->first();
            $payload = [
                'parent_id' => $parents[$def['parent']],
                'name' => $def['name'],
                'guard_name' => $def['guard'],
                'description' => $def['desc'],
                'crm_permission' => 1,
                'updated_at' => now(),
            ];

            if ($permission) {
                if ($permission->trashed()) {
                    $permission->restore();
                }
                $permission->fill($payload)->save();
            } else {
                $payload['created_at'] = now();
                $permission = Permission::create($payload);
            }

            $guardIds[$def['guard']] = $permission->id;
        }

        $this->command?->info('Goal permissions seeded (' . count($guardIds) . ' guards).');

        return $guardIds;
    }

    protected function attachRolePermissions(array $guardIds): void
    {
        $goalAll = array_values($guardIds);
        // MD / CEO: every CRM permission across all modules (not only Goal guards)
        $allModulePermissionIds = Permission::query()
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('permissions', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $matrix = [
            4 => '__ALL_MODULES__', // MD
            5 => '__ALL_MODULES__', // CEO
            6 => [ // RD
                'goal_analytics_guard', 'mis_reports_guard', 'scheduled_reports_guard',
                'view_leads_guard', 'view_loan_cases_guard', 'view_targets_guard',
                'branch_mis_guard', 'revenue_ledger_guard', 'manage_users_guard', 'manage_org_units_guard',
                'manage_company_guard', 'view_audit_logs_guard',
                'manage_products_guard', 'manage_dispositions_guard', 'manage_sources_guard',
            ],
            7 => [ // Cluster
                'goal_analytics_guard', 'mis_reports_guard',
                'view_leads_guard', 'view_loan_cases_guard', 'view_targets_guard', 'manage_targets_guard',
                'branch_mis_guard', 'revenue_ledger_guard', 'manage_users_guard', 'view_audit_logs_guard',
                'manage_products_guard', 'manage_dispositions_guard', 'manage_sources_guard',
            ],
            8 => [ // BM — can create/revise branch & team targets (approval by Cluster+)
                'goal_analytics_guard', 'mis_reports_guard',
                'vrm_calling_guard', 'view_leads_guard', 'create_lead_guard', 'channel_lead_guard',
                'bm_allocation_guard', 'view_loan_cases_guard', 'edit_case_workspace_guard',
                'customer_visits_guard', 'case_documents_guard', 'bank_processing_guard', 'sanction_disbursement_guard',
                'view_targets_guard', 'manage_targets_guard', 'branch_mis_guard', 'revenue_ledger_guard',
                'manage_users_guard',
                'manage_products_guard', 'manage_dispositions_guard', 'manage_sources_guard',
            ],
            9 => [ // VRM — calling only; masters assigned separately if needed
                'vrm_calling_guard', 'create_lead_guard', 'channel_lead_guard', 'view_leads_guard',
            ],
            10 => [ // JSM
                'view_leads_guard', 'view_loan_cases_guard', 'edit_case_workspace_guard',
                'customer_visits_guard', 'case_documents_guard', 'bank_processing_guard', 'sanction_disbursement_guard',
                'record_disbursement_guard', 'branch_mis_guard', 'revenue_ledger_guard',
            ],
            11 => [ // SM
                'view_leads_guard', 'view_loan_cases_guard', 'edit_case_workspace_guard',
                'customer_visits_guard', 'case_documents_guard', 'bank_processing_guard', 'sanction_disbursement_guard',
                'record_disbursement_guard', 'branch_mis_guard', 'revenue_ledger_guard',
            ],
            12 => [ // SSM
                'view_leads_guard', 'view_loan_cases_guard', 'edit_case_workspace_guard',
                'customer_visits_guard', 'case_documents_guard', 'bank_processing_guard', 'sanction_disbursement_guard',
                'record_disbursement_guard', 'branch_mis_guard', 'revenue_ledger_guard',
            ],
            13 => [ // PMS
                'pms_verification_guard', 'view_loan_cases_guard', 'case_documents_guard', 'revenue_ledger_guard',
            ],
            14 => [ // Accounts
                'accounts_queue_guard', 'accounts_approve_guard', 'payout_processing_guard', 'record_receipt_guard',
                'revenue_ledger_guard', 'view_loan_cases_guard',
            ],
        ];

        // Classic CRM controllers still authorize with manage_crm_* / view_crm_activity_log_guard.
        // Attach those when a Goal role has the matching Goal organisation guard so sidebar
        // modules and page checks stay aligned after role → crm_page_right sync.
        $classicBridge = [
            'manage_users_guard' => [
                'manage_crm_user_guard', 'view_crm_user_guard', 'create_crm_user_guard', 'edit_crm_user_guard',
                'view_crm_activity_log_guard',
            ],
            'manage_roles_guard' => [
                'manage_crm_role_guard', 'create_crm_role_guard', 'edit_crm_role_guard',
                'view_crm_activity_log_guard',
            ],
            'manage_permissions_guard' => [
                'manage_crm_permission_guard', 'create_crm_permission_guard', 'edit_crm_permission_guard',
            ],
            'manage_company_guard' => [
                'manage_crm_organization_guard', 'view_crm_organization_guard',
            ],
            'view_audit_logs_guard' => [
                'view_crm_activity_log_guard',
            ],
            'manage_products_guard' => [
                'manage_crm_product_guard', 'view_crm_product_guard', 'create_crm_product_guard',
                'edit_crm_product_guard', 'delete_crm_product_guard',
            ],
            'manage_sources_guard' => [
                'manage_crm_lead_source_guard', 'create_crm_lead_source_guard',
                'edit_crm_lead_source_guard', 'delete_crm_lead_source_guard',
            ],
        ];

        $classicGuardIds = Permission::query()
            ->whereIn('guard_name', collect($classicBridge)->flatten()->unique()->values()->all())
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('permissions', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at')
            )
            ->pluck('id', 'guard_name')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($matrix as $roleId => $guards) {
            if (!Role::query()->where('id', $roleId)->exists()) {
                continue;
            }

            if ($guards === '__ALL_MODULES__') {
                $permissionIds = $allModulePermissionIds;
            } else {
                $permissionIds = array_values(array_filter(array_map(fn ($g) => $guardIds[$g] ?? null, $guards)));

                foreach ($guards as $goalGuard) {
                    foreach ($classicBridge[$goalGuard] ?? [] as $classicGuard) {
                        if (isset($classicGuardIds[$classicGuard])) {
                            $permissionIds[] = $classicGuardIds[$classicGuard];
                        }
                    }
                }
                $permissionIds = array_values(array_unique($permissionIds));
            }

            $existing = DB::table('role_has_permission')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach (array_diff($permissionIds, $existing) as $permissionId) {
                DB::table('role_has_permission')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        // Admin (1) also gets Goal guards so platform Admin can configure Goal.
        if (Role::query()->where('id', 1)->exists()) {
            $existing = DB::table('role_has_permission')->where('role_id', 1)->pluck('permission_id')->map(fn ($id) => (int) $id)->all();
            foreach (array_diff($goalAll, $existing) as $permissionId) {
                DB::table('role_has_permission')->insert([
                    'role_id' => 1,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        $this->command?->info('Goal role ↔ permission matrix synced (MD/CEO = all modules).');
    }
}
