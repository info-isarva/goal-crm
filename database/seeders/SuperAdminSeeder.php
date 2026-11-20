<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Ensure default CRM roles exist, then create/update the Super Admin user.
     *
     * Role IDs are fixed because the app treats:
     * - 0 = Super Admin (user.crm_role_type only; not a roles row)
     * - 1 = Admin
     * Legacy Manager (2) / Employee (3) are removed — Goal hierarchy uses IDs 4–14.
     */
    public function run(): void
    {
        $this->ensureDefaultRoles();
        $this->removeLegacyManagerEmployeeRoles();
        $this->attachAllPermissionsToAdmin();
        $this->seedSuperAdmin();
    }

    protected function ensureDefaultRoles(): void
    {
        $defaults = [
            1 => ['name' => 'Admin', 'description' => 'Full company admin (except Super Admin–only settings)'],
        ];

        foreach ($defaults as $id => $data) {
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

                $this->command?->info("Role checked/updated: {$data['name']} (id {$id})");
                continue;
            }

            $byName = Role::withTrashed()->where('name', $data['name'])->first();
            if ($byName) {
                $this->command?->warn(
                    "Role '{$data['name']}' already exists as id {$byName->id}; expected id {$id}. Leaving as-is."
                );
                continue;
            }

            DB::table('roles')->insert([
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Role created: {$data['name']} (id {$id})");
        }

        $missing = collect($defaults)
            ->keys()
            ->reject(fn ($id) => Role::where('id', $id)->exists());

        if ($missing->isNotEmpty()) {
            $this->command?->warn(
                'Expected role ids missing after seed: ' . $missing->implode(', ')
            );
        }
    }

    protected function removeLegacyManagerEmployeeRoles(): void
    {
        $legacyIds = [2, 3]; // Manager, Employee

        DB::table('role_has_permission')->whereIn('role_id', $legacyIds)->delete();

        Role::withTrashed()->whereIn('id', $legacyIds)->forceDelete();
        Role::withTrashed()->whereIn('name', ['Manager', 'Employee'])->forceDelete();

        $this->command?->info('Removed legacy Manager / Employee roles from database.');
    }

    protected function attachAllPermissionsToAdmin(): void
    {
        $admin = Role::find(1);
        if (!$admin) {
            $this->command?->warn('Admin role (id 1) not found; skipping permission attach.');
            return;
        }

        $permissionIds = Permission::query()->pluck('id')->all();
        if ($permissionIds === []) {
            $this->command?->warn('No permissions found to attach to Admin.');
            return;
        }

        $existing = DB::table('role_has_permission')
            ->where('role_id', $admin->id)
            ->pluck('permission_id')
            ->all();

        $toAttach = array_diff($permissionIds, $existing);
        foreach ($toAttach as $permissionId) {
            DB::table('role_has_permission')->insert([
                'role_id' => $admin->id,
                'permission_id' => $permissionId,
            ]);
        }

        $this->command?->info(
            'Admin role permissions checked (' . count($permissionIds) . ' guards, '
            . count($toAttach) . ' newly attached).'
        );
    }

    protected function seedSuperAdmin(): void
    {
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@gmail.com');
        $password = env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::query()->where('email', $email)->first();

        $payload = [
            'name' => $name,
            'password' => Hash::make($password),
            'status' => 1,
            'crm_role_type' => 0,
            'crm_page_right' => json_encode(['all']),
            'email_verified_at' => now(),
            'compliance_consent_agreed' => true,
            'compliance_consent_agreed_at' => now(),
            '2fa_enabled' => 0,
        ];

        if ($user) {
            $user->fill($payload)->save();
            $this->command?->info("Super Admin updated: {$email} (crm_role_type=0)");
        } else {
            User::create(array_merge(['email' => $email], $payload));
            $this->command?->info("Super Admin created: {$email} (crm_role_type=0)");
        }

        $this->command?->info("Login with {$email} / {$password}");
    }
}
