<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('parent_permissions')
            ->where('name', 'Quotation')
            ->value('id');

        if (!$parentId) {
            $parentId = DB::table('parent_permissions')->insertGetId([
                'name' => 'Quotation',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissions = [
            [
                'guard_name' => 'manage_crm_quotation_terms_guard',
                'name' => 'Manage Quotation Terms & Conditions',
                'description' => 'Allows managing default quotation terms and conditions in Settings.',
            ],
            [
                'guard_name' => 'manage_crm_quotation_email_templates_guard',
                'name' => 'Manage Quotation Email Templates',
                'description' => 'Allows designing and managing quotation email templates in Settings.',
            ],
        ];

        $newPermissionIds = [];
        $newGuards = [];

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
                $newGuards[] = $permission['guard_name'];
            }
        }

        // Also ensure PDF quotation template manage permission is linked for Super Admin roles.
        $templateManageId = DB::table('permissions')
            ->where('guard_name', 'manage_crm_quotation_templates_guard')
            ->value('id');

        $legacyPermissionIds = DB::table('permissions')
            ->whereIn('guard_name', [
                'manage_crm_quotations_guard',
                'manage_crm_quotation_templates_guard',
            ])
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

        // Grant to Super Admin users and anyone who already manages quotations/templates.
        $users = DB::table('users')->select('id', 'crm_role_type', 'crm_page_right')->get();
        $legacyGuards = [
            'manage_crm_quotations_guard',
            'manage_crm_quotation_templates_guard',
        ];

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
            if ($templateManageId && $isSuperAdmin && !in_array('manage_crm_quotation_templates_guard', $merged, true)) {
                $merged[] = 'manage_crm_quotation_templates_guard';
            }

            if ($merged === $rights) {
                continue;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'crm_page_right' => json_encode(array_values(array_unique($merged))),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $guards = [
            'manage_crm_quotation_terms_guard',
            'manage_crm_quotation_email_templates_guard',
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
