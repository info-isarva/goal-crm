<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Get or create Parent Permission: Web Forms Management
        $webFormsParentId = DB::table('parent_permissions')
            ->where('name', 'Web Forms Management')
            ->orWhere('name', 'Web Forms')
            ->value('id');

        if (!$webFormsParentId) {
            $webFormsParentId = DB::table('parent_permissions')->insertGetId([
                'name' => 'Web Forms Management',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Get or create Parent Permission: Social Ads Management
        $socialAdsParentId = DB::table('parent_permissions')
            ->where('name', 'Social Ads Management')
            ->orWhere('name', 'Social Ads')
            ->value('id');

        if (!$socialAdsParentId) {
            $socialAdsParentId = DB::table('parent_permissions')->insertGetId([
                'name' => 'Social Ads Management',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Define permissions
        $permissions = [
            // Web Forms
            [
                'parent_id' => $webFormsParentId,
                'guard_name' => 'manage_crm_web_forms_guard',
                'name' => 'Manage CRM Web Forms',
                'description' => 'Full management access to web forms, embed codes, and submissions.',
            ],
            [
                'parent_id' => $webFormsParentId,
                'guard_name' => 'view_crm_web_forms_guard',
                'name' => 'View CRM Web Forms',
                'description' => 'Allows viewing web forms list, embed codes, and submissions.',
            ],
            [
                'parent_id' => $webFormsParentId,
                'guard_name' => 'create_crm_web_forms_guard',
                'name' => 'Create CRM Web Forms',
                'description' => 'Allows creating new web forms.',
            ],
            [
                'parent_id' => $webFormsParentId,
                'guard_name' => 'edit_crm_web_forms_guard',
                'name' => 'Edit CRM Web Forms',
                'description' => 'Allows editing web forms, toggling active status, and regenerating embed codes.',
            ],
            [
                'parent_id' => $webFormsParentId,
                'guard_name' => 'delete_crm_web_forms_guard',
                'name' => 'Delete CRM Web Forms',
                'description' => 'Allows deleting web forms.',
            ],

            // Social Ads
            [
                'parent_id' => $socialAdsParentId,
                'guard_name' => 'manage_crm_social_ads_guard',
                'name' => 'Manage CRM Social Ads',
                'description' => 'Full management access to social ads integrations, lead forms, and webhook configurations.',
            ],
            [
                'parent_id' => $socialAdsParentId,
                'guard_name' => 'view_crm_social_ads_guard',
                'name' => 'View CRM Social Ads',
                'description' => 'Allows viewing social ads dashboard, platforms, connected accounts, and synced leads.',
            ],
            [
                'parent_id' => $socialAdsParentId,
                'guard_name' => 'create_crm_social_ads_guard',
                'name' => 'Create CRM Social Ads',
                'description' => 'Allows creating generic webhook endpoints and connecting new ad accounts.',
            ],
            [
                'parent_id' => $socialAdsParentId,
                'guard_name' => 'edit_crm_social_ads_guard',
                'name' => 'Edit CRM Social Ads',
                'description' => 'Allows updating credentials, field mappings, lead defaults, syncing leads, and retrying failed leads.',
            ],
            [
                'parent_id' => $socialAdsParentId,
                'guard_name' => 'delete_crm_social_ads_guard',
                'name' => 'Delete CRM Social Ads',
                'description' => 'Allows deleting forms and disconnecting social ad accounts.',
            ],
        ];

        $insertedPermIds = [];
        $newGuards = [];

        foreach ($permissions as $permission) {
            $newGuards[] = $permission['guard_name'];
            $existing = DB::table('permissions')->where('guard_name', $permission['guard_name'])->first();

            if ($existing) {
                DB::table('permissions')
                    ->where('id', $existing->id)
                    ->update([
                        'parent_id' => $permission['parent_id'],
                        'name' => $permission['name'],
                        'description' => $permission['description'],
                        'crm_permission' => 1,
                        'deleted_at' => null,
                        'updated_at' => now(),
                    ]);
                $insertedPermIds[] = (int) $existing->id;
            } else {
                $id = DB::table('permissions')->insertGetId([
                    'parent_id' => $permission['parent_id'],
                    'guard_name' => $permission['guard_name'],
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'crm_permission' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertedPermIds[] = (int) $id;
            }
        }

        // 4. Attach permissions to the Admin role (ID: 1) in role_has_permission
        $adminRole = DB::table('roles')->where('id', 1)->first() ?? DB::table('roles')->where('name', 'Admin')->first();
        if ($adminRole) {
            foreach ($insertedPermIds as $permId) {
                DB::table('role_has_permission')->updateOrInsert([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        // 5. Add new guards to Company's admin_assignable_permissions so Admins can assign them
        $companies = DB::table('companies')->get();
        foreach ($companies as $comp) {
            $assignable = json_decode($comp->admin_assignable_permissions ?? '[]', true);
            if (!is_array($assignable)) {
                $assignable = [];
            }
            $updatedAssignable = array_values(array_unique(array_merge($assignable, $newGuards)));
            DB::table('companies')
                ->where('id', $comp->id)
                ->update([
                    'admin_assignable_permissions' => json_encode($updatedAssignable),
                    'updated_at' => now(),
                ]);
        }

        // 6. Grant to existing Admin users (role 1) so they have immediate access
        $adminUsers = DB::table('users')->where('crm_role_type', 1)->get();
        foreach ($adminUsers as $adminUser) {
            $rights = json_decode($adminUser->crm_page_right ?? '[]', true);
            if (!is_array($rights)) {
                $rights = [];
            }
            if (in_array('all', $rights, true)) {
                continue;
            }
            $merged = array_values(array_unique(array_merge($rights, $newGuards)));
            DB::table('users')
                ->where('id', $adminUser->id)
                ->update([
                    'crm_page_right' => json_encode($merged),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $guards = [
            'manage_crm_web_forms_guard',
            'view_crm_web_forms_guard',
            'create_crm_web_forms_guard',
            'edit_crm_web_forms_guard',
            'delete_crm_web_forms_guard',
            'manage_crm_social_ads_guard',
            'view_crm_social_ads_guard',
            'create_crm_social_ads_guard',
            'edit_crm_social_ads_guard',
            'delete_crm_social_ads_guard',
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

        // Clean from companies admin_assignable_permissions
        $companies = DB::table('companies')->get();
        foreach ($companies as $comp) {
            $assignable = json_decode($comp->admin_assignable_permissions ?? '[]', true);
            if (is_array($assignable)) {
                $updatedAssignable = array_values(array_diff($assignable, $guards));
                DB::table('companies')
                    ->where('id', $comp->id)
                    ->update([
                        'admin_assignable_permissions' => json_encode($updatedAssignable),
                        'updated_at' => now(),
                    ]);
            }
        }

        // Clean from users crm_page_right
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
