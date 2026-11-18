<?php

namespace Database\Factories;

use App\Models\GoalLoanCase;
use App\Models\GoalOrgUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GoalLoanCase> */
class GoalLoanCaseFactory extends Factory
{
    protected $model = GoalLoanCase::class;

    public function definition(): array
    {
        return [
            'case_no' => 'CS-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'goal_org_unit_id' => GoalOrgUnit::factory(),
            'owner_id' => User::factory(),
            'status' => 'Draft',
            'customer_name' => fake()->name(),
            'mobile' => '9'.fake()->numerify('#########'),
            'product' => 'Home Loan',
            'loan_amount' => 1000000,
            'sanction_amount' => 1000000,
            'sanction_date' => now()->toDateString(),
        ];
    }

    public function sanctioned(): static
    {
        return $this->state(fn () => ['status' => 'Sanctioned']);
    }
}
