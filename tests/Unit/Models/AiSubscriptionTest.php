<?php

namespace Ajooda\AiMetering\Tests\Unit\Models;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_active_when_ends_at_is_null(): void
    {
        $subscription = AiSubscription::factory()->active()->create([
            'ends_at' => null,
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isExpired());
    }

    public function test_is_active_when_ends_at_is_future(): void
    {
        $subscription = AiSubscription::factory()->create([
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue($subscription->isActive());
    }

    public function test_is_expired_when_ends_at_is_past(): void
    {
        $subscription = AiSubscription::factory()->expired()->create([
            'ends_at' => now()->subDay(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertTrue($subscription->isExpired());
    }

    public function test_is_active_during_grace_period(): void
    {
        $subscription = AiSubscription::factory()->create([
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isInGracePeriod());
    }

    public function test_is_in_trial_when_trial_ends_at_is_future(): void
    {
        $subscription = AiSubscription::factory()->create([
            'trial_ends_at' => now()->addWeek(),
        ]);

        $this->assertTrue($subscription->isInTrial());
    }

    public function test_has_plan_relationship(): void
    {
        $plan = AiPlan::factory()->create();
        $subscription = AiSubscription::factory()->create([
            'ai_plan_id' => $plan->id,
        ]);

        $this->assertEquals($plan->id, $subscription->plan->id);
    }
}
