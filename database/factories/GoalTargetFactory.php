<?php

namespace Database\Factories;

use App\Models\GoalOrgUnit;
use App\Models\GoalTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GoalTarget> */
class GoalTargetFactory extends Factory
{
    protected $model = GoalTarget::class;

    public function definition(): array
    {
        return [
            'period' => now()->format('Y-m'),
            'metric' => 'revenue',
            'goal_org_unit_id' => GoalOrgUnit::factory(),
            'user_id' => null,
            'base_target' => 100000,
            'opening_shortfall' => 0,
            'adjustments' => 0,
            'actual' => 0,
            'closing_shortfall' => 100000,
            'status' => 'Draft',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'Approved']);
    }
}
