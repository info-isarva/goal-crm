<?php

namespace Tests\Feature\Goal;

use App\Models\GoalLoanLead;
use App\Services\Goal\GoalPartyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalPartyDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_mobile_shares_party(): void
    {
        $svc = app(GoalPartyService::class);

        $a = GoalLoanLead::factory()->create(['mobile' => '9876543210', 'customer_name' => 'Asha']);
        $b = GoalLoanLead::factory()->create(['mobile' => '98765-43210', 'customer_name' => 'Asha Two']);

        $partyA = $svc->attachLead($a);
        $partyB = $svc->attachLead($b);

        $this->assertNotNull($partyA);
        $this->assertSame($partyA->id, $partyB->id);
        $this->assertSame($partyA->id, $a->fresh()->goal_party_id);
        $this->assertSame($partyA->id, $b->fresh()->goal_party_id);

        $matches = $svc->matchesForMobile('9876543210', $a->id);
        $this->assertTrue($matches['leads']->contains('id', $b->id));
    }

    public function test_normalize_mobile_keeps_last_ten_digits(): void
    {
        $this->assertSame('9876543210', GoalPartyService::normalizeMobile('+91 98765 43210'));
        $this->assertNull(GoalPartyService::normalizeMobile('abc'));
    }
}
