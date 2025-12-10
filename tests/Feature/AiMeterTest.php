<?php

namespace Ajooda\AiMetering\Tests\Feature;

use Ajooda\AiMetering\Exceptions\AiLimitExceededException;
use Ajooda\AiMetering\Facades\AiMeter;
use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiMeterTest extends TestCase
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

    public function test_records_usage_when_call_succeeds(): void
    {
        $user = $this->createUser();
        $this->createUnlimitedPlan($user);

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

        $this->assertNotNull($response);
        $this->assertDatabaseHas('ai_usages', [
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'feature' => 'test',
        ]);
    }

    public function test_throws_exception_when_limit_exceeded(): void
    {
        $user = $this->createUser();
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => 1000,
        ]);

        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);

        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 1000,
            'occurred_at' => now(),
        ]);

        $this->expectException(AiLimitExceededException::class);

        AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) [
                    'usage' => (object) [
                        'total_tokens' => 100,
                    ],
                ];
            });
    }

    public function test_allows_usage_when_within_limit(): void
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

        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'total_tokens' => 5000,
            'occurred_at' => now(),
        ]);

        $response = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) [
                    'usage' => (object) [
                        'total_tokens' => 100,
                    ],
                ];
            });

        $this->assertNotNull($response);
        $this->assertFalse($response->isLimitReached());
    }

    public function test_handles_manual_usage(): void
    {
        $user = $this->createUser();
        $this->createUnlimitedPlan($user);

        $response = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('manual', 'custom-model')
            ->withManualUsage([
                'input_tokens' => 100,
                'output_tokens' => 200,
                'total_tokens' => 300,
            ])
            ->call(function () {
                return 'response';
            });

        $this->assertNotNull($response);
        $this->assertEquals(300, $response->getUsage()->totalTokens);
    }

    public function test_respects_idempotency_key(): void
    {
        $user = $this->createUser();
        $this->createUnlimitedPlan($user);

        $key = 'test-idempotency-key-123';

        // First call
        AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->withIdempotencyKey($key)
            ->call(function () {
                return (object) ['usage' => (object) ['total_tokens' => 100]];
            });

        AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->withIdempotencyKey($key)
            ->call(function () {
                return (object) ['usage' => (object) ['total_tokens' => 100]];
            });

        $this->assertEquals(1, AiUsage::where('idempotency_key', $key)->count());
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

    protected function createUnlimitedPlan($user): void
    {
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => null,
            'monthly_cost_limit' => null,
        ]);

        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
            'ends_at' => null,
        ]);
    }
}
