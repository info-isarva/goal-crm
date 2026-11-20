<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            SuperAdminSeeder::class,
            GoalRolesPermissionsSeeder::class,
            GoalVrmDispositionSeeder::class,
            GoalOrgUnitSeeder::class,
            GoalHierarchyUsersSeeder::class,
        ]);
    }
}
