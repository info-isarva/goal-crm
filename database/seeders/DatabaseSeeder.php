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
            // Goal roles + Masters/dispositions permissions first so Admin attach sees them
            GoalRolesPermissionsSeeder::class,
            SuperAdminSeeder::class,
            GoalVrmDispositionSeeder::class,
            GoalLeadSourceSeeder::class,
            GoalOrgUnitSeeder::class,
            GoalHierarchyUsersSeeder::class,
        ]);
    }
}
