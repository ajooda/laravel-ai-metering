<?php

namespace Ajooda\AiMetering\Tests\Unit\Services\Providers;

use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Services\Providers\AnthropicProvider;
use Ajooda\AiMetering\Tests\TestCase;

class AnthropicProviderTest extends TestCase
{
    public function test_extracts_usage_from_anthropic_response(): void
    {
        config([
            'ai-metering.providers.anthropic.models.claude-3-5-sonnet' => [
                'input_price_per_1k' => 0.003,
                'output_price_per_1k' => 0.015,
            ],
        ]);

        $provider = new AnthropicProvider(new CostCalculator, 'claude-3-5-sonnet');

        $response = (object) [
            'usage' => (object) [
                'input_tokens' => 100,
                'output_tokens' => 200,
            ],
        ];

        $result = $provider->call(fn () => $response);

        $this->assertNotNull($result['response']);
        $this->assertNotNull($result['usage']);
        $this->assertEquals(100, $result['usage']->inputTokens);
        $this->assertEquals(200, $result['usage']->outputTokens);
        $this->assertEquals(300, $result['usage']->totalTokens);
    }

    public function test_handles_array_response(): void
    {
        config([
            'ai-metering.providers.anthropic.models.claude-3-5-sonnet' => [
                'input_price_per_1k' => 0.003,
                'output_price_per_1k' => 0.015,
            ],
        ]);

        $provider = new AnthropicProvider(new CostCalculator, 'claude-3-5-sonnet');

        $response = [
            'usage' => [
                'input_tokens' => 150,
                'output_tokens' => 250,
            ],
        ];

        $result = $provider->call(fn () => $response);

        $this->assertEquals(150, $result['usage']->inputTokens);
        $this->assertEquals(250, $result['usage']->outputTokens);
    }

    public function test_handles_missing_usage(): void
    {
        $provider = new AnthropicProvider(new CostCalculator, 'claude-3-5-sonnet');

        $response = (object) [];

        $result = $provider->call(fn () => $response);

        $this->assertNotNull($result['usage']);
        $this->assertNull($result['usage']->inputTokens);
    }
}
