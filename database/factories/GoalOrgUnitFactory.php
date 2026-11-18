<?php

namespace Database\Factories;

use App\Models\GoalOrgUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<GoalOrgUnit> */
class GoalOrgUnitFactory extends Factory
{
    protected $model = GoalOrgUnit::class;

    public function definition(): array
    {
        return [
            'type' => GoalOrgUnit::TYPE_BRANCH,
            'parent_id' => null,
            'name' => fake()->unique()->city().' Branch',
            'code' => strtoupper(Str::random(6)),
            'is_active' => true,
        ];
    }

    public function region(): static
    {
        return $this->state(fn () => ['type' => GoalOrgUnit::TYPE_REGION, 'parent_id' => null]);
    }

    public function cluster(): static
    {
        return $this->state(fn () => ['type' => GoalOrgUnit::TYPE_CLUSTER]);
    }

    public function branch(): static
    {
        return $this->state(fn () => ['type' => GoalOrgUnit::TYPE_BRANCH]);
    }
}
