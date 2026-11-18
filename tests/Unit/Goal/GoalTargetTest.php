<?php

namespace Tests\Unit\Goal;

use App\Models\GoalTarget;
use PHPUnit\Framework\TestCase;

class GoalTargetTest extends TestCase
{
    public function test_effective_target_sums_components(): void
    {
        $t = new GoalTarget([
            'base_target' => 100,
            'opening_shortfall' => 25,
            'adjustments' => 5,
            'actual' => 0,
        ]);

        $this->assertSame(130.0, $t->effectiveTarget());
    }

    public function test_closing_shortfall_never_negative(): void
    {
        $t = new GoalTarget([
            'base_target' => 100,
            'opening_shortfall' => 0,
            'adjustments' => 0,
            'actual' => 150,
        ]);
        $t->recalculateClosingShortfall();

        $this->assertSame(0.0, (float) $t->closing_shortfall);
    }

    public function test_closing_shortfall_is_effective_minus_actual(): void
    {
        $t = new GoalTarget([
            'base_target' => 100,
            'opening_shortfall' => 20,
            'adjustments' => -5,
            'actual' => 40,
        ]);
        $t->recalculateClosingShortfall();

        $this->assertSame(75.0, (float) $t->closing_shortfall);
    }
}
