<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('parent_permissions')
            ->where('name', 'Settings')
            ->value('id');

        if (!$parentId) {
            $parentId = DB::table('parent_permissions')->insertGetId([
                'name' => 'Settings',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $settingsPerms = [
            [
                'guard_name' => 'manage_crm_outreach_type_guard',
                'name' => 'Manage Outreach Types',
                'description' => 'Allows managing outreach types used in daily reports.',
            ],
            [
                'guard_name' => 'create_crm_outreach_type_guard',
                'name' => 'Create Outreach Types',
                'description' => 'Allows creating outreach types.',
            ],
            [
                'guard_name' => 'edit_crm_outreach_type_guard',
                'name' => 'Edit Outreach Types',
                'description' => 'Allows editing outreach types.',
            ],
            [
                'guard_name' => 'delete_crm_outreach_type_guard',
                'name' => 'Delete Outreach Types',
                'description' => 'Allows deleting outreach types.',
            ],
        ];

        foreach ($settingsPerms as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['guard_name' => $permission['guard_name']],
                [
                    'parent_id' => $parentId,
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'crm_permission' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $reportsParentId = DB::table('parent_permissions')
            ->where('name', 'Reports')
            ->value('id');

        if (!$reportsParentId) {
            $reportsParentId = DB::table('parent_permissions')->insertGetId([
                'name' => 'Reports',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $reportPerms = [
            [
                'guard_name' => 'manage_crm_daily_report_guard',
                'name' => 'Manage Daily Reports',
                'description' => 'Allows accessing the daily report module.',
            ],
            [
                'guard_name' => 'create_crm_daily_report_guard',
                'name' => 'Create Daily Reports',
                'description' => 'Allows submitting daily reports.',
            ],
            [
                'guard_name' => 'view_crm_daily_report_guard',
                'name' => 'View Daily Reports',
                'description' => 'Allows viewing daily reports.',
            ],
            [
                'guard_name' => 'edit_crm_daily_report_guard',
                'name' => 'Edit Daily Reports',
                'description' => 'Allows editing own daily reports.',
            ],
        ];

        foreach ($reportPerms as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['guard_name' => $permission['guard_name']],
                [
                    'parent_id' => $reportsParentId,
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'crm_permission' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('guard_name', [
                'manage_crm_outreach_type_guard',
                'create_crm_outreach_type_guard',
                'edit_crm_outreach_type_guard',
                'delete_crm_outreach_type_guard',
                'manage_crm_daily_report_guard',
                'create_crm_daily_report_guard',
                'view_crm_daily_report_guard',
                'edit_crm_daily_report_guard',
            ])
            ->delete();
    }
};
