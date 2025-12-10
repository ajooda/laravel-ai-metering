<?php

namespace Ajooda\AiMetering\Services;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Services\Providers\AnthropicProvider;
use Ajooda\AiMetering\Services\Providers\ManualProvider;
use Ajooda\AiMetering\Services\Providers\OpenAiProvider;
use Illuminate\Support\Facades\Config;

class ProviderFactory
{
    public function __construct(
        protected CostCalculator $costCalculator
    ) {}

    /**
     * Create a provider client instance.
     */
    public function make(string $provider, string $model): ProviderClient
    {
        $providerConfig = Config::get("ai-metering.providers.{$provider}", []);

        if (empty($providerConfig) || ! isset($providerConfig['class'])) {
            throw new \InvalidArgumentException("Provider '{$provider}' is not configured.");
        }

        $providerClass = $providerConfig['class'];

        return match ($provider) {
            'openai' => new OpenAiProvider($this->costCalculator, $model),
            'anthropic' => new AnthropicProvider($this->costCalculator, $model),
            'manual' => new ManualProvider,
            default => $this->createCustomProvider($providerClass, $provider, $model),
        };
    }

    /**
     * Create a custom provider instance.
     */
    protected function createCustomProvider(string $class, string $provider, string $model): ProviderClient
    {
        if (! class_exists($class)) {
            throw new \InvalidArgumentException("Provider class '{$class}' does not exist.");
        }

        if (! is_subclass_of($class, ProviderClient::class)) {
            throw new \InvalidArgumentException("Provider class '{$class}' must implement ProviderClient interface.");
        }

        try {
            return new $class($this->costCalculator, $model);
        } catch (\TypeError $e) {
            try {
                return new $class($model);
            } catch (\TypeError $e2) {
                return new $class;
            }
        }
    }
}
