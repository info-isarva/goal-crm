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
                'guard_name' => 'preview_crm_quotation_templates_guard',
                'name' => 'Preview Quotation Templates',
                'description' => 'Allows previewing quotation PDF templates.',
            ],
            [
                'guard_name' => 'duplicate_crm_quotation_templates_guard',
                'name' => 'Duplicate Quotation Templates',
                'description' => 'Allows duplicating quotation PDF templates.',
            ],
            [
                'guard_name' => 'set_default_crm_quotation_templates_guard',
                'name' => 'Set Default Quotation Template',
                'description' => 'Allows setting a quotation PDF template as default.',
            ],
            [
                'guard_name' => 'deactivate_crm_quotation_templates_guard',
                'name' => 'Activate/Deactivate Quotation Templates',
                'description' => 'Allows activating or deactivating quotation PDF templates.',
            ],
        ];

        $newPermissionIds = [];
        $newGuards = array_column($permissions, 'guard_name');

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

            $id = DB::table('permissions')
                ->where('guard_name', $permission['guard_name'])
                ->value('id');

            if ($id) {
                $newPermissionIds[] = (int) $id;
            }
        }

        $legacyGuards = [
            'manage_crm_quotation_templates_guard',
            'create_crm_quotation_templates_guard',
            'edit_crm_quotation_templates_guard',
            'delete_crm_quotation_templates_guard',
        ];

        $legacyPermissionIds = DB::table('permissions')
            ->whereIn('guard_name', $legacyGuards)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($legacyPermissionIds) && !empty($newPermissionIds)) {
            $roleIds = DB::table('role_has_permission')
                ->whereIn('permission_id', $legacyPermissionIds)
                ->pluck('role_id')
                ->unique()
                ->all();

            foreach ($roleIds as $roleId) {
                foreach ($newPermissionIds as $permissionId) {
                    $exists = DB::table('role_has_permission')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        DB::table('role_has_permission')->insert([
                            'role_id' => $roleId,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
        }

        $users = DB::table('users')->select('id', 'crm_role_type', 'crm_page_right')->get();
        foreach ($users as $user) {
            $rights = json_decode($user->crm_page_right ?? '[]', true);
            if (!is_array($rights)) {
                continue;
            }

            if (in_array('all', $rights, true)) {
                continue;
            }

            $isSuperAdmin = (int) ($user->crm_role_type ?? -1) === 0;
            $hasLegacy = (bool) array_intersect($legacyGuards, $rights);

            if (!$isSuperAdmin && !$hasLegacy) {
                continue;
            }

            $merged = array_values(array_unique(array_merge($rights, $newGuards)));
            if ($merged === $rights) {
                continue;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'crm_page_right' => json_encode($merged),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $guards = [
            'preview_crm_quotation_templates_guard',
            'duplicate_crm_quotation_templates_guard',
            'set_default_crm_quotation_templates_guard',
            'deactivate_crm_quotation_templates_guard',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('guard_name', $guards)
            ->pluck('id')
            ->all();

        if (!empty($permissionIds)) {
            DB::table('role_has_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')
            ->whereIn('guard_name', $guards)
            ->delete();

        $users = DB::table('users')->select('id', 'crm_page_right')->get();
        foreach ($users as $user) {
            $rights = json_decode($user->crm_page_right ?? '[]', true);
            if (!is_array($rights) || in_array('all', $rights, true)) {
                continue;
            }

            $filtered = array_values(array_diff($rights, $guards));
            if ($filtered === $rights) {
                continue;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'crm_page_right' => json_encode($filtered),
                    'updated_at' => now(),
                ]);
        }
    }
};
