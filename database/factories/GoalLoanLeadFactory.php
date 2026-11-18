<?php

namespace Database\Factories;

use App\Models\GoalLoanLead;
use App\Models\GoalOrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GoalLoanLead> */
class GoalLoanLeadFactory extends Factory
{
    protected $model = GoalLoanLead::class;

    public function definition(): array
    {
        return [
            'ref_no' => 'LD-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'customer_name' => fake()->name(),
            'mobile' => '9'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'source' => 'manual',
            'product' => 'Home Loan',
            'loan_amount' => 1000000,
            'lead_type' => 'immediate',
            'status' => 'Pending BM Allocation',
            'consent_recorded' => true,
            'goal_org_unit_id' => GoalOrgUnit::factory(),
            'created_by' => User::factory(),
        ];
    }
}
