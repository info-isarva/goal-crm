<?php

namespace Database\Seeders;

use App\Models\GoalBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GoalBankSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['HDFC Bank', 'HDFC'],
            ['ICICI Bank', 'ICICI'],
            ['State Bank of India', 'SBI'],
            ['Axis Bank', 'AXIS'],
            ['Kotak Mahindra Bank', 'KOTAK'],
            ['Bank of Baroda', 'BOB'],
            ['Punjab National Bank', 'PNB'],
            ['Canara Bank', 'CANARA'],
            ['Union Bank of India', 'UNION'],
            ['Yes Bank', 'YES'],
            ['IDFC First Bank', 'IDFC'],
            ['IndusInd Bank', 'INDUS'],
        ];

        foreach ($defaults as $i => [$name, $code]) {
            $existing = GoalBank::withTrashed()
                ->where(function ($q) use ($name, $code) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                        ->orWhereRaw('UPPER(code) = ?', [Str::upper($code)]);
                })
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->fill([
                    'name' => $name,
                    'code' => Str::upper($code),
                    'slug' => Str::slug($name),
                    'sort_order' => ($i + 1) * 10,
                    'is_active' => true,
                ])->save();
                continue;
            }

            GoalBank::create([
                'name' => $name,
                'code' => Str::upper($code),
                'slug' => Str::slug($name),
                'sort_order' => ($i + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
