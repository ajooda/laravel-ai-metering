<?php

namespace Ajooda\AiMetering\Tests\Unit\Services;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Models\AiUsageLimitOverride;
use Ajooda\AiMetering\Services\PlanResolver;
use Ajooda\AiMetering\Services\UsageLimiter;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsageLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected UsageLimiter $limiter;

    protected PlanResolver $planResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planResolver = new PlanResolver;
        $this->limiter = new UsageLimiter($this->planResolver);

        config([
            'ai-metering.period' => [
                'type' => 'monthly',
                'alignment' => 'calendar',
                'timezone' => 'UTC',
            ],
            'ai-metering.performance.cache_limit_checks' => false,
        ]);
    }

    public function test_returns_unlimited_for_no_billable(): void
    {
        $result = $this->limiter->checkLimit(null);

        $this->assertTrue($result->allowed);
        $this->assertFalse($result->hardLimitReached);
        $this->assertNull($result->remainingTokens);
    }

    public function test_returns_unlimited_for_no_subscription(): void
    {
        $user = $this->createUser();

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertNull($result->remainingTokens);
    }

    public function test_returns_unlimited_for_unlimited_plan(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->unlimited()->create();
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertNull($result->remainingTokens);
        $this->assertNull($result->remainingCost);
    }

    public function test_checks_token_limit(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create some usage
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 5000,
            'occurred_at' => now()->startOfMonth(),
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertEquals(5000, $result->remainingTokens);
        $this->assertEquals(50.0, $result->usagePercentage);
    }

    public function test_detects_limit_exceeded(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create usage that exceeds limit
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 10000,
            'occurred_at' => now()->startOfMonth(),
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertFalse($result->allowed);
        $this->assertTrue($result->hardLimitReached);
        $this->assertEquals(0, $result->remainingTokens);
    }

    public function test_detects_approaching_limit(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create usage at 85%
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 8500,
            'occurred_at' => now()->startOfMonth(),
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertTrue($result->approaching);
        $this->assertEquals(85.0, $result->usagePercentage);
    }

    public function test_checks_cost_limit(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_cost_limit' => 100.00,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create some usage
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_cost' => 50.00,
            'occurred_at' => now()->startOfMonth(),
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertEquals(50.0, $result->remainingCost);
        $this->assertEquals(50.0, $result->usagePercentage);
    }

    public function test_respects_override_limits(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create override with higher limit
        // Override period must cover the entire current period
        // Since period_end is exclusive, override.period_end should be >= next period start
        AiUsageLimitOverride::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'period_start' => now()->startOfMonth()->subDay(), // Start before period
            'period_end' => now()->endOfMonth()->addDay(), // End after period
            'token_limit' => 20000,
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertEquals(20000, $result->remainingTokens);
    }

    public function test_prevents_usage_when_requested_tokens_exceed_limit(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create usage at 9500 tokens
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 9500,
            'occurred_at' => now()->startOfMonth(),
        ]);

        // Try to use 1000 more tokens (would exceed limit)
        $result = $this->limiter->checkLimit($user, requestedTokens: 1000);

        $this->assertFalse($result->allowed);
        $this->assertTrue($result->hardLimitReached);
    }

    public function test_only_counts_usage_in_current_period(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        // Create usage in previous month
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 50000,
            'occurred_at' => now()->subMonth(),
        ]);

        // Create usage in current month
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 5000,
            'occurred_at' => now()->startOfMonth(),
        ]);

        $result = $this->limiter->checkLimit($user);

        $this->assertTrue($result->allowed);
        $this->assertEquals(5000, $result->remainingTokens); // Should only count current month
    }

    protected function createUser()
    {
        return new class
        {
            public $id = 1;

            public function getMorphClass(): string
            {
                return 'App\Models\User';
            }
        };
    }
}
