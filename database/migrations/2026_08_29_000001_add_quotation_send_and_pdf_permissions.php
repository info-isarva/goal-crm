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
                'guard_name' => 'send_email_crm_quotations_guard',
                'name' => 'Send Quotation Email',
                'description' => 'Allows sending quotation emails with PDF attachment.',
            ],
            [
                'guard_name' => 'send_whatsapp_crm_quotations_guard',
                'name' => 'Send Quotation WhatsApp',
                'description' => 'Allows sending quotations via WhatsApp with PDF attachment.',
            ],
            [
                'guard_name' => 'download_pdf_crm_quotations_guard',
                'name' => 'View/Download Quotation PDF',
                'description' => 'Allows viewing and downloading quotation PDFs.',
            ],
        ];

        $newPermissionIds = [];

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
            'manage_crm_quotations_guard',
            'view_crm_quotations_guard',
        ];

        $legacyPermissionIds = DB::table('permissions')
            ->whereIn('guard_name', $legacyGuards)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Grant new permissions to roles that already have quotation view/manage.
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

        // Keep existing users able to email/WhatsApp/PDF until rights are tightened in Users UI.
        $newGuards = array_column($permissions, 'guard_name');
        $users = DB::table('users')->select('id', 'crm_page_right')->get();

        foreach ($users as $user) {
            $rights = json_decode($user->crm_page_right ?? '[]', true);
            if (!is_array($rights)) {
                continue;
            }

            if (in_array('all', $rights, true)) {
                continue;
            }

            $hasLegacy = (bool) array_intersect($legacyGuards, $rights);
            if (!$hasLegacy) {
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
            'send_email_crm_quotations_guard',
            'send_whatsapp_crm_quotations_guard',
            'download_pdf_crm_quotations_guard',
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
