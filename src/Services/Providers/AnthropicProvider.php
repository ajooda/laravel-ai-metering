<?php

namespace Ajooda\AiMetering\Services\Providers;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Support\ProviderUsage;

class AnthropicProvider implements ProviderClient
{
    public function __construct(
        protected CostCalculator $costCalculator,
        protected string $model
    ) {}

    /**
     * Execute the Anthropic callback and extract usage information.
     */
    public function call(callable $callback): array
    {
        $response = $callback();

        $usage = $this->extractUsage($response);

        return [
            'response' => $response,
            'usage' => $usage,
        ];
    }

    /**
     * Extract usage information from Anthropic response.
     */
    protected function extractUsage($response): ProviderUsage
    {
        $usageData = null;

        if (is_object($response) && isset($response->usage)) {
            $usageData = $response->usage;
        } elseif (is_array($response) && isset($response['usage'])) {
            $usageData = $response['usage'];
        }

        if ($usageData) {
            $inputTokens = $this->getProperty($usageData, 'input_tokens');
            $outputTokens = $this->getProperty($usageData, 'output_tokens');

            $costs = $this->costCalculator->calculate(
                'anthropic',
                $this->model,
                $inputTokens,
                $outputTokens,
                null
            );

            return new ProviderUsage(
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
                totalTokens: ($inputTokens ?? 0) + ($outputTokens ?? 0),
                inputCost: $costs['input_cost'],
                outputCost: $costs['output_cost'],
                totalCost: $costs['total_cost'],
                currency: 'usd'
            );
        }

        return new ProviderUsage;
    }

    /**
     * Get property from object or array.
     */
    protected function getProperty($data, string $key): ?int
    {
        if (is_object($data)) {
            return $data->$key ?? null;
        }

        if (is_array($data)) {
            return $data[$key] ?? null;
        }

        return null;
    }
}
