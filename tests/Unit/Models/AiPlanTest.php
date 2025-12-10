<?php

namespace Ajooda\AiMetering\Tests\Unit\Models;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_unlimited_tokens_when_null(): void
    {
        $plan = AiPlan::factory()->unlimited()->create();

        $this->assertTrue($plan->hasUnlimitedTokens());
        $this->assertTrue($plan->hasUnlimitedCost());
    }

    public function test_has_limited_tokens_when_set(): void
    {
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);

        $this->assertFalse($plan->hasUnlimitedTokens());
    }

    public function test_allows_overage_when_price_set(): void
    {
        $plan = AiPlan::factory()->create([
            'overage_price_per_1k_tokens' => 0.01,
        ]);

        $this->assertTrue($plan->allowsOverage());
    }

    public function test_does_not_allow_overage_when_price_null(): void
    {
        $plan = AiPlan::factory()->create([
            'overage_price_per_1k_tokens' => null,
        ]);

        $this->assertFalse($plan->allowsOverage());
    }

    public function test_has_subscriptions_relationship(): void
    {
        $plan = AiPlan::factory()->create();
        AiSubscription::factory()->count(3)->create([
            'ai_plan_id' => $plan->id,
        ]);

        $this->assertEquals(3, $plan->subscriptions()->count());
    }
}
