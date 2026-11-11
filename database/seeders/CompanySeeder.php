<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a single company record if none exists
        if (!Company::exists()) {
            Company::create([
                'name' => 'Isarva',
                'logo' => null,
                'address' => 'Headquarters',
                'city' => 'City',
                'pincode' => '000000',
                'phone' => '+0000000000',
            ]);
        }
    }
}
