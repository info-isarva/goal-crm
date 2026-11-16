<?php

namespace Database\Seeders;

use App\Models\GoalOrgUnit;
use Illuminate\Database\Seeder;

class GoalOrgUnitSeeder extends Seeder
{
    /**
     * Sample hierarchy matching the prototype demo scopes.
     */
    public function run(): void
    {
        $region = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'SOUTH'],
            [
                'type' => GoalOrgUnit::TYPE_REGION,
                'parent_id' => null,
                'name' => 'South Region',
                'is_active' => true,
            ]
        );

        $cluster = GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'BLR-CLU'],
            [
                'type' => GoalOrgUnit::TYPE_CLUSTER,
                'parent_id' => $region->id,
                'name' => 'Bangalore Cluster',
                'is_active' => true,
            ]
        );

        GoalOrgUnit::query()->updateOrCreate(
            ['code' => 'HAL-BR'],
            [
                'type' => GoalOrgUnit::TYPE_BRANCH,
                'parent_id' => $cluster->id,
                'name' => 'Halasuru Branch',
                'is_active' => true,
            ]
        );

        $this->command?->info('Sample Goal org tree: South Region → Bangalore Cluster → Halasuru Branch');
    }
}
