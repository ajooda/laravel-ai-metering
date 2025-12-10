<?php

namespace Ajooda\AiMetering\Tests\Unit\Services\Providers;

use Ajooda\AiMetering\Services\Providers\ManualProvider;
use Ajooda\AiMetering\Tests\TestCase;

class ManualProviderTest extends TestCase
{
    public function test_uses_manual_usage_when_set(): void
    {
        $provider = new ManualProvider;
        $provider->setUsage([
            'input_tokens' => 100,
            'output_tokens' => 200,
            'total_tokens' => 300,
        ]);

        $result = $provider->call(fn () => 'response');

        $this->assertEquals('response', $result['response']);
        $this->assertEquals(100, $result['usage']->inputTokens);
        $this->assertEquals(200, $result['usage']->outputTokens);
        $this->assertEquals(300, $result['usage']->totalTokens);
    }

    public function test_returns_empty_usage_when_not_set(): void
    {
        $provider = new ManualProvider;

        $result = $provider->call(fn () => 'response');

        $this->assertEquals('response', $result['response']);
        $this->assertNull($result['usage']->inputTokens);
        $this->assertNull($result['usage']->totalTokens);
    }

    public function test_can_create_with_usage_statically(): void
    {
        $provider = ManualProvider::withUsage([
            'total_tokens' => 500,
        ]);

        $result = $provider->call(fn () => 'response');

        $this->assertEquals(500, $result['usage']->totalTokens);
    }
}
