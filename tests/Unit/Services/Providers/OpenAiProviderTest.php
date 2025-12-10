<?php

namespace Ajooda\AiMetering\Tests\Unit\Services\Providers;

use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Services\Providers\OpenAiProvider;
use Ajooda\AiMetering\Tests\TestCase;

class OpenAiProviderTest extends TestCase
{
    public function test_extracts_usage_from_openai_response(): void
    {
        config([
            'ai-metering.providers.openai.models.gpt-4o-mini' => [
                'input_price_per_1k' => 0.00015,
                'output_price_per_1k' => 0.00060,
            ],
        ]);

        $provider = new OpenAiProvider(new CostCalculator, 'gpt-4o-mini');

        $response = (object) [
            'usage' => (object) [
                'prompt_tokens' => 100,
                'completion_tokens' => 200,
                'total_tokens' => 300,
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
            'ai-metering.providers.openai.models.gpt-4o-mini' => [
                'input_price_per_1k' => 0.00015,
                'output_price_per_1k' => 0.00060,
            ],
        ]);

        $provider = new OpenAiProvider(new CostCalculator, 'gpt-4o-mini');

        $response = [
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 200,
                'total_tokens' => 300,
            ],
        ];

        $result = $provider->call(fn () => $response);

        $this->assertNotNull($result['usage']);
        $this->assertEquals(100, $result['usage']->inputTokens);
    }

    public function test_handles_missing_usage(): void
    {
        $provider = new OpenAiProvider(new CostCalculator, 'gpt-4o-mini');

        $response = (object) [];

        $result = $provider->call(fn () => $response);

        $this->assertNotNull($result['usage']);
        $this->assertNull($result['usage']->inputTokens);
    }
}
