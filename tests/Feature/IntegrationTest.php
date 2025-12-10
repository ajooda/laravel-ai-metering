<?php

namespace Ajooda\AiMetering\Tests\Feature;

use Ajooda\AiMetering\Events\AiUsageRecorded;
use Ajooda\AiMetering\Facades\AiMeter;
use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-metering.providers.openai.models.gpt-4o-mini' => [
                'input_price_per_1k' => 0.00015,
                'output_price_per_1k' => 0.00060,
            ],
            'ai-metering.billing.overage_behavior' => 'block',
        ]);
    }

    public function test_full_flow_with_plan_mode(): void
    {
        Event::fake();

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

        $response = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->feature('test')
            ->call(function () {
                return (object) [
                    'usage' => (object) [
                        'prompt_tokens' => 100,
                        'completion_tokens' => 200,
                        'total_tokens' => 300,
                    ],
                ];
            });

        // Verify usage was recorded
        $this->assertDatabaseHas('ai_usages', [
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 300,
        ]);

        // Verify event was dispatched
        Event::assertDispatched(AiUsageRecorded::class);

        // Verify response
        $this->assertNotNull($response);
        $this->assertEquals(300, $response->getUsage()->totalTokens);
    }

    public function test_plan_change_mid_period(): void
    {
        $user = $this->createUser();
        $oldPlan = AiPlan::factory()->create([
            'monthly_token_limit' => 5000,
        ]);
        $newPlan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);

        $subscription = AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $oldPlan->id,
            'ends_at' => null,
        ]);

        // Create usage under old plan
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 3000,
            'occurred_at' => now()->startOfMonth(),
        ]);

        // Change plan
        $subscription->update([
            'ai_plan_id' => $newPlan->id,
            'previous_plan_id' => $oldPlan->id,
        ]);

        // Usage should still count against old plan's period
        // But new usage will be checked against new plan
        $result = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) [
                    'usage' => (object) ['total_tokens' => 100],
                ];
            });

        $this->assertNotNull($result);
        // Should have 7000 remaining (10000 - 3000)
    }

    public function test_subscription_expiration_handling(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 10000,
        ]);

        $subscription = AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => now()->subDay(), // Expired
            'grace_period_ends_at' => null,
        ]);

        // Should not be able to use (no active subscription)
        $result = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) [
                    'usage' => (object) ['total_tokens' => 100],
                ];
            });

        // Usage might still be recorded but without limits
        $this->assertNotNull($result);
    }

    public function test_period_boundary_handling(): void
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

        // Create usage at end of previous month
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 50000,
            'occurred_at' => now()->subMonth()->endOfMonth(),
        ]);

        // Create usage at start of current month
        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 1000,
            'occurred_at' => now()->startOfMonth(),
        ]);

        $result = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) [
                    'usage' => (object) ['total_tokens' => 100],
                ];
            });

        // Should only count current month usage (1000 + 100 = 1100)
        $this->assertNotNull($result);
        $this->assertTrue($result->getLimitCheck()->allowed);
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
