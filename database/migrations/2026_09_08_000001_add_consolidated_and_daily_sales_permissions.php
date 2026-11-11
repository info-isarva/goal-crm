<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Get or create parent permission "Reports"
        $reportsParentId = DB::table('parent_permissions')
            ->where('name', 'Reports')
            ->orWhere('name', 'Report Management')
            ->value('id');

        if (!$reportsParentId) {
            $reportsParentId = DB::table('parent_permissions')->insertGetId([
                'name' => 'Reports',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $newPerms = [
            [
                'guard_name' => 'consolidated_sales_report_guard',
                'name' => 'Consolidated Sales Report',
                'description' => 'Allows accessing and exporting Consolidated Sales Report.',
            ],
            [
                'guard_name' => 'sales_marketing_daily_report_guard',
                'name' => 'Daily Sales & Marketing Report',
                'description' => 'Allows accessing and exporting Daily Sales & Marketing Report.',
            ],
        ];

        $insertedPermIds = [];

        foreach ($newPerms as $permission) {
            $exists = DB::table('permissions')->where('guard_name', $permission['guard_name'])->first();
            if ($exists) {
                $insertedPermIds[] = $exists->id;
            } else {
                $id = DB::table('permissions')->insertGetId([
                    'parent_id' => $reportsParentId,
                    'guard_name' => $permission['guard_name'],
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'crm_permission' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertedPermIds[] = $id;
            }
        }

        // 2. Attach permissions to all existing roles
        $roles = DB::table('roles')->get();
        foreach ($roles as $role) {
            foreach ($insertedPermIds as $permId) {
                DB::table('role_has_permission')->updateOrInsert([
                    'role_id' => $role->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        // 3. Grant access to each user in users table
        $users = DB::table('users')->select('id', 'crm_page_right')->get();
        $targetGuards = [
            'consolidated_sales_report_guard',
            'sales_marketing_daily_report_guard',
            'manage_crm_daily_report_guard',
            'create_crm_daily_report_guard',
            'view_crm_daily_report_guard',
            'edit_crm_daily_report_guard',
        ];

        foreach ($users as $user) {
            $rights = json_decode($user->crm_page_right ?? '[]', true);
            if (!is_array($rights)) {
                $rights = [];
            }

            $merged = array_values(array_unique(array_merge($rights, $targetGuards)));

            DB::table('users')
                ->where('id', $user->id)
                ->update(['crm_page_right' => json_encode($merged)]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('guard_name', [
                'consolidated_sales_report_guard',
                'sales_marketing_daily_report_guard',
            ])
            ->delete();
    }
};
