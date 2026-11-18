<?php

namespace Tests\Unit\Goal;

use App\Services\Goal\GoalBranchMisService;
use PHPUnit\Framework\TestCase;

class GoalBranchMisServiceTest extends TestCase
{
    public function test_recalculate_commercials_from_disb_and_rates(): void
    {
        $svc = new GoalBranchMisService;
        $out = $svc->recalculate([
            'disb_amount' => 1000000,
            'payin_pct' => 1.0,   // 1%
            'payout_pct' => 0.5,  // 0.5%
        ]);

        $this->assertSame(10000.0, $out['payin_amt']);
        $this->assertSame(5000.0, $out['payout_amount']);
        $this->assertSame(5000.0, $out['net_revenue']);
    }
}
