<?php

namespace Ajooda\AiMetering\Tests\Helpers;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Models\AiUsage;

trait TestHelpers
{
    /**
     * Create a test user model.
     */
    protected function createTestUser(int $id = 1): object
    {
        return new class($id)
        {
            public $id;

            public function __construct($id)
            {
                $this->id = $id;
            }

            public function getMorphClass(): string
            {
                return 'App\Models\User';
            }
        };
    }

    /**
     * Create a test tenant model.
     */
    protected function createTestTenant(string $id = 'tenant-1'): object
    {
        return new class($id)
        {
            public $id;

            public function __construct($id)
            {
                $this->id = $id;
            }

            public function getMorphClass(): string
            {
                return 'App\Models\Tenant';
            }
        };
    }

    /**
     * Create an unlimited plan for a billable entity.
     */
    protected function createUnlimitedPlan($billable): AiSubscription
    {
        $plan = AiPlan::factory()->unlimited()->create();

        return AiSubscription::factory()->active()->create([
            'billable_type' => get_class($billable),
            'billable_id' => $billable->id,
            'ai_plan_id' => $plan->id,
        ]);
    }

    /**
     * Create a limited plan for a billable entity.
     */
    protected function createLimitedPlan($billable, ?int $tokenLimit = null, ?float $costLimit = null): AiSubscription
    {
        $plan = AiPlan::factory()->create([
            'monthly_token_limit' => $tokenLimit,
            'monthly_cost_limit' => $costLimit,
        ]);

        return AiSubscription::factory()->active()->create([
            'billable_type' => get_class($billable),
            'billable_id' => $billable->id,
            'ai_plan_id' => $plan->id,
        ]);
    }

    /**
     * Create usage records for a billable entity.
     */
    protected function createUsage($billable, int $tokens, ?float $cost = null, $occurredAt = null): AiUsage
    {
        return AiUsage::factory()->create([
            'billable_type' => get_class($billable),
            'billable_id' => $billable->id,
            'total_tokens' => $tokens,
            'total_cost' => $cost ?? ($tokens * 0.001), // Default cost calculation
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Create a mock OpenAI response.
     */
    protected function createOpenAiResponse(int $promptTokens = 100, int $completionTokens = 200, int $totalTokens = 300): object
    {
        return (object) [
            'usage' => (object) [
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
            ],
            'choices' => [
                (object) [
                    'message' => (object) [
                        'content' => 'Test response',
                    ],
                ],
            ],
        ];
    }

    /**
     * Create a mock Anthropic response.
     */
    protected function createAnthropicResponse(int $inputTokens = 100, int $outputTokens = 200): object
    {
        return (object) [
            'usage' => (object) [
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ],
            'content' => [
                (object) [
                    'text' => 'Test response',
                ],
            ],
        ];
    }

    /**
     * Assert usage was recorded in database.
     */
    protected function assertUsageRecorded($billable, array $attributes = []): void
    {
        $this->assertDatabaseHas('ai_usages', array_merge([
            'billable_type' => get_class($billable),
            'billable_id' => $billable->id,
        ], $attributes));
    }

    /**
     * Assert usage was not recorded.
     */
    protected function assertUsageNotRecorded($billable, array $attributes = []): void
    {
        $this->assertDatabaseMissing('ai_usages', array_merge([
            'billable_type' => get_class($billable),
            'billable_id' => $billable->id,
        ], $attributes));
    }
}
