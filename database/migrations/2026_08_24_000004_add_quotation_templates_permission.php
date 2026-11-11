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

        $permissions = [
            [
                'guard_name' => 'manage_crm_quotation_templates_guard',
                'name' => 'Manage Quotation Templates',
                'description' => 'Allows Super Admin to manage quotation HTML/CSS templates.',
            ],
            [
                'guard_name' => 'create_crm_quotation_templates_guard',
                'name' => 'Create Quotation Templates',
                'description' => 'Allows creating quotation templates.',
            ],
            [
                'guard_name' => 'edit_crm_quotation_templates_guard',
                'name' => 'Edit Quotation Templates',
                'description' => 'Allows editing quotation templates.',
            ],
            [
                'guard_name' => 'delete_crm_quotation_templates_guard',
                'name' => 'Delete Quotation Templates',
                'description' => 'Allows deleting unused quotation templates.',
            ],
        ];

        foreach ($permissions as $permission) {
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
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('guard_name', [
                'manage_crm_quotation_templates_guard',
                'create_crm_quotation_templates_guard',
                'edit_crm_quotation_templates_guard',
                'delete_crm_quotation_templates_guard',
            ])
            ->delete();
    }
};
