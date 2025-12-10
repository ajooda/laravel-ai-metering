<?php

namespace Ajooda\AiMetering\Tests\Unit\Services;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Services\ProviderFactory;
use Ajooda\AiMetering\Services\Providers\AnthropicProvider;
use Ajooda\AiMetering\Services\Providers\ManualProvider;
use Ajooda\AiMetering\Services\Providers\OpenAiProvider;
use Ajooda\AiMetering\Tests\TestCase;

class ProviderFactoryTest extends TestCase
{
    protected ProviderFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ProviderFactory(new CostCalculator);

        config([
            'ai-metering.providers.openai' => [
                'class' => OpenAiProvider::class,
            ],
            'ai-metering.providers.anthropic' => [
                'class' => AnthropicProvider::class,
            ],
            'ai-metering.providers.manual' => [
                'class' => ManualProvider::class,
            ],
        ]);
    }

    public function test_creates_openai_provider(): void
    {
        $provider = $this->factory->make('openai', 'gpt-4o-mini');

        $this->assertInstanceOf(ProviderClient::class, $provider);
        $this->assertInstanceOf(OpenAiProvider::class, $provider);
    }

    public function test_creates_anthropic_provider(): void
    {
        $provider = $this->factory->make('anthropic', 'claude-3-5-sonnet');

        $this->assertInstanceOf(ProviderClient::class, $provider);
        $this->assertInstanceOf(AnthropicProvider::class, $provider);
    }

    public function test_creates_manual_provider(): void
    {
        $provider = $this->factory->make('manual', 'custom-model');

        $this->assertInstanceOf(ProviderClient::class, $provider);
        $this->assertInstanceOf(ManualProvider::class, $provider);
    }

    public function test_throws_exception_for_unconfigured_provider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'unknown' is not configured.");

        $this->factory->make('unknown', 'model');
    }

    public function test_throws_exception_for_missing_provider_class(): void
    {
        config([
            'ai-metering.providers.custom' => [
                'class' => 'NonExistentClass',
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider class 'NonExistentClass' does not exist.");

        $this->factory->make('custom', 'model');
    }

    public function test_throws_exception_for_invalid_provider_class(): void
    {
        config([
            'ai-metering.providers.invalid' => [
                'class' => \stdClass::class,
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider class 'stdClass' must implement ProviderClient interface.");

        $this->factory->make('invalid', 'model');
    }
}
