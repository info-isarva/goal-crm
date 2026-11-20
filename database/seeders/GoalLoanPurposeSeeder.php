<?php

namespace Database\Seeders;

use App\Models\GoalLoanPurpose;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GoalLoanPurposeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'Home Renovation',
            'Education',
            'Medical',
            'Wedding',
            'Business Expansion',
            'Debt Consolidation',
            'Travel',
            'Other',
        ];

        foreach ($defaults as $i => $name) {
            $existing = GoalLoanPurpose::withTrashed()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->fill([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'sort_order' => ($i + 1) * 10,
                    'is_active' => true,
                ])->save();
                continue;
            }

            GoalLoanPurpose::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'sort_order' => ($i + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
