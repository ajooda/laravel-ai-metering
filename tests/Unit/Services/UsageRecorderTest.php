<?php

namespace Ajooda\AiMetering\Tests\Unit\Services;

use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Services\UsageRecorder;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsageRecorderTest extends TestCase
{
    use RefreshDatabase;

    protected UsageRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recorder = new UsageRecorder;

        config([
            'ai-metering.performance.queue_usage_recording' => false,
            'ai-metering.logging.enabled' => false,
        ]);
    }

    public function test_records_usage_to_database(): void
    {
        $data = [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'user_id' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'feature' => 'test',
            'input_tokens' => 100,
            'output_tokens' => 200,
            'total_tokens' => 300,
            'input_cost' => 0.00015,
            'output_cost' => 0.00030,
            'total_cost' => 0.00045,
            'currency' => 'usd',
            'occurred_at' => now(),
        ];

        $usage = $this->recorder->record($data);

        $this->assertInstanceOf(AiUsage::class, $usage);
        $this->assertDatabaseHas('ai_usages', [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'total_tokens' => 300,
        ]);
    }

    public function test_respects_idempotency_key(): void
    {
        $key = 'test-idempotency-key-123';

        $data = [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'total_tokens' => 100,
            'total_cost' => 0.1,
            'idempotency_key' => $key,
            'occurred_at' => now(),
        ];

        // Record first time
        $first = $this->recorder->record($data);

        // Record second time with same key
        $second = $this->recorder->record($data);

        // Should return the same record
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, AiUsage::where('idempotency_key', $key)->count());
    }

    public function test_records_batch_usage(): void
    {
        $usages = [
            [
                'billable_type' => 'App\Models\User',
                'billable_id' => 1,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'total_tokens' => 100,
                'total_cost' => 0.1,
                'occurred_at' => now(),
            ],
            [
                'billable_type' => 'App\Models\User',
                'billable_id' => 1,
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'total_tokens' => 200,
                'total_cost' => 0.2,
                'occurred_at' => now(),
            ],
        ];

        $recorded = $this->recorder->recordBatch($usages);

        $this->assertCount(2, $recorded);
        $this->assertEquals(2, AiUsage::count());
    }

    public function test_handles_nullable_fields(): void
    {
        $data = [
            'billable_type' => null,
            'billable_id' => null,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'total_tokens' => 100,
            'total_cost' => 0.1,
            'occurred_at' => now(),
        ];

        $usage = $this->recorder->record($data);

        $this->assertNotNull($usage);
        $this->assertNull($usage->billable_type);
        $this->assertNull($usage->user_id);
        $this->assertNull($usage->feature);
    }

    public function test_uses_default_currency(): void
    {
        $data = [
            'billable_type' => null,
            'billable_id' => null,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'total_tokens' => 100,
            'total_cost' => 0.1,
            'occurred_at' => now(),
        ];

        $usage = $this->recorder->record($data);

        $this->assertEquals('usd', $usage->currency);
    }
}
