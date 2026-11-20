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
        $company = Company::query()->first();

        $payload = [
            'name' => 'Goal Corporation',
            'address' => 'Headquarters',
            'city' => 'Bangalore',
            'pincode' => '560001',
            'phone' => '+910000000000',
        ];

        if ($company) {
            $company->fill(array_merge($payload, [
                'logo' => $company->logo,
            ]))->save();
            return;
        }

        Company::create(array_merge($payload, [
            'logo' => null,
        ]));
    }
}
