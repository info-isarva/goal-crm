<?php

namespace Database\Seeders;

use App\Models\GoalLoanLead;
use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class GoalLeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'VRM', 'description' => 'Created from VRM Calling'],
            ['name' => 'Channel', 'description' => 'Channel / partner sourced'],
            ['name' => 'Manual', 'description' => 'Manually entered lead'],
            ['name' => 'Walk-in', 'description' => 'Branch walk-in customer'],
            ['name' => 'Referral', 'description' => 'Customer or partner referral'],
        ];

        foreach ($defaults as $row) {
            LeadSource::withTrashed()
                ->whereRaw('LOWER(name) = ?', [strtolower($row['name'])])
                ->first()
                ?->restore();

            LeadSource::query()->updateOrCreate(
                ['name' => $row['name']],
                ['description' => $row['description']]
            );
        }

        // Normalize legacy Goal lead source slugs to master names.
        $map = [
            'vrm' => 'VRM',
            'vrm calling' => 'VRM',
            'channel' => 'Channel',
            'channel partner' => 'Channel',
            'manual' => 'Manual',
            'walkin' => 'Walk-in',
            'walk-in' => 'Walk-in',
            'referral' => 'Referral',
        ];

        foreach ($map as $from => $to) {
            GoalLoanLead::query()
                ->whereRaw('LOWER(TRIM(source)) = ?', [$from])
                ->update(['source' => $to]);
        }
    }
}
