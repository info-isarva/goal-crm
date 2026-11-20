<?php

namespace Database\Seeders;

use App\Models\GoalVrmDisposition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GoalVrmDispositionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Interested', 'creates_lead' => true, 'maps_to_lead_type' => 'immediate', 'sort_order' => 10],
            ['name' => 'Not Interested', 'creates_lead' => false, 'maps_to_lead_type' => null, 'sort_order' => 20],
            ['name' => 'No Answer', 'creates_lead' => false, 'maps_to_lead_type' => null, 'sort_order' => 30],
            ['name' => 'Busy', 'creates_lead' => false, 'maps_to_lead_type' => null, 'sort_order' => 40],
            ['name' => 'Callback', 'creates_lead' => true, 'maps_to_lead_type' => 'future', 'sort_order' => 50],
            ['name' => 'Invalid Number', 'creates_lead' => false, 'maps_to_lead_type' => null, 'sort_order' => 60],
        ];

        foreach ($rows as $row) {
            $existing = GoalVrmDisposition::withTrashed()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($row['name'])])
                ->first();

            $payload = [
                'name' => $row['name'],
                'slug' => Str::slug($row['name']),
                'creates_lead' => $row['creates_lead'],
                'maps_to_lead_type' => $row['maps_to_lead_type'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->fill($payload)->save();
                continue;
            }

            GoalVrmDisposition::create($payload);
        }

        $this->command?->info('VRM dispositions seeded ('.count($rows).').');
    }
}
