<?php

namespace Ajooda\AiMetering\Services\Providers;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Support\ProviderUsage;

class OpenAiProvider implements ProviderClient
{
    public function __construct(
        protected CostCalculator $costCalculator,
        protected string $model
    ) {}

    /**
     * Execute the OpenAI callback and extract usage information.
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
     * Extract usage information from OpenAI response.
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
            // Try both camelCase and snake_case property names
            $inputTokens = $this->getProperty($usageData, 'promptTokens')
                ?? $this->getProperty($usageData, 'prompt_tokens')
                ?? $this->getProperty($usageData, 'inputTokens')
                ?? $this->getProperty($usageData, 'input_tokens');

            $outputTokens = $this->getProperty($usageData, 'completionTokens')
                ?? $this->getProperty($usageData, 'completion_tokens')
                ?? $this->getProperty($usageData, 'outputTokens')
                ?? $this->getProperty($usageData, 'output_tokens');

            $totalTokens = $this->getProperty($usageData, 'totalTokens')
                ?? $this->getProperty($usageData, 'total_tokens');

            $costs = $this->costCalculator->calculate(
                'openai',
                $this->model,
                $inputTokens,
                $outputTokens,
                $totalTokens
            );

            return new ProviderUsage(
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
                totalTokens: $totalTokens,
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
