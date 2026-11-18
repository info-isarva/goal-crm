<?php

namespace Tests\Feature\Goal;

use App\Models\GoalOrgUnit;
use App\Models\GoalTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalCarryForwardTargetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_carry_forward_creates_next_period_draft_opening(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $sourcePeriod = now()->subMonthNoOverflow()->format('Y-m');
        $nextPeriod = now()->format('Y-m');

        $src = GoalTarget::factory()->approved()->create([
            'period' => $sourcePeriod,
            'metric' => 'revenue',
            'goal_org_unit_id' => $org->id,
            'user_id' => null,
            'base_target' => 100,
            'opening_shortfall' => 0,
            'adjustments' => 0,
            'actual' => 40,
            'closing_shortfall' => 60,
        ]);
        $src->recalculateClosingShortfall();
        $src->save();

        $this->artisan('goal:carry-forward-targets', ['--period' => $sourcePeriod])
            ->assertSuccessful();

        $next = GoalTarget::query()
            ->where('period', $nextPeriod)
            ->where('metric', 'revenue')
            ->where('goal_org_unit_id', $org->id)
            ->whereNull('user_id')
            ->first();

        $this->assertNotNull($next);
        $this->assertSame('Draft', $next->status);
        $this->assertEquals(60.0, (float) $next->opening_shortfall);
    }

    public function test_dry_run_does_not_write(): void
    {
        $org = GoalOrgUnit::factory()->create();
        $sourcePeriod = '2026-01';

        GoalTarget::factory()->approved()->create([
            'period' => $sourcePeriod,
            'metric' => 'disbursement',
            'goal_org_unit_id' => $org->id,
            'user_id' => null,
            'base_target' => 100,
            'actual' => 0,
            'closing_shortfall' => 100,
        ]);

        $before = GoalTarget::query()->count();
        $this->artisan('goal:carry-forward-targets', [
            '--period' => $sourcePeriod,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame($before, GoalTarget::query()->count());
    }
}
