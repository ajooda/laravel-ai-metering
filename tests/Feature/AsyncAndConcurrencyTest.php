<?php

namespace Ajooda\AiMetering\Tests\Feature;

use Ajooda\AiMetering\Facades\AiMeter;
use Ajooda\AiMetering\Jobs\RecordAiUsage;
use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;

class AsyncAndConcurrencyTest extends TestCase
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
        ]);
    }

    public function test_dispatches_job_when_queue_enabled(): void
    {
        Bus::fake();

        Config::set('ai-metering.performance.queue_usage_recording', 'default');

        $user = $this->createUser();
        $this->createUnlimitedPlan($user);

        AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) ['usage' => (object) ['total_tokens' => 100]];
            });

        Bus::assertDispatched(RecordAiUsage::class);
    }

    public function test_acquires_lock_when_race_condition_protection_enabled(): void
    {
        Config::set('ai-metering.security.prevent_race_conditions', true);

        $user = $this->createUser();
        $this->createUnlimitedPlan($user);

        $response = AiMeter::forUser($user)
            ->billable($user)
            ->usingProvider('openai', 'gpt-4o-mini')
            ->call(function () {
                return (object) ['usage' => (object) ['total_tokens' => 100]];
            });

        $this->assertNotNull($response);
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
        $plan = AiPlan::factory()->create(['monthly_token_limit' => null]);
        AiSubscription::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
            'ai_plan_id' => $plan->id,
        ]);
    }
}
