<?php

namespace Ajooda\AiMetering\Tests\Unit\Models;

use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_usage_record(): void
    {
        $usage = AiUsage::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
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
        ]);

        $this->assertDatabaseHas('ai_usages', [
            'id' => $usage->id,
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
        ]);
    }

    public function test_scope_for_billable(): void
    {
        $user = new class
        {
            public $id = 1;

            public function getMorphClass(): string
            {
                return 'App\Models\User';
            }
        };

        AiUsage::factory()->create([
            'billable_type' => get_class($user),
            'billable_id' => $user->id,
        ]);

        AiUsage::factory()->create([
            'billable_type' => 'App\Models\Tenant',
            'billable_id' => 1,
        ]);

        $usages = AiUsage::forBillable($user)->get();

        $this->assertCount(1, $usages);
        $this->assertEquals(get_class($user), $usages->first()->billable_type);
    }

    public function test_scope_by_provider(): void
    {
        AiUsage::factory()->create(['provider' => 'openai']);
        AiUsage::factory()->create(['provider' => 'anthropic']);

        $usages = AiUsage::byProvider('openai')->get();

        $this->assertCount(1, $usages);
        $this->assertEquals('openai', $usages->first()->provider);
    }

    public function test_scope_in_period(): void
    {
        AiUsage::factory()->create([
            'occurred_at' => now()->subMonth(),
        ]);

        AiUsage::factory()->create([
            'occurred_at' => now(),
        ]);

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $usages = AiUsage::inPeriod($start, $end)->get();

        $this->assertCount(1, $usages);
    }
}
