<?php

namespace Ajooda\AiMetering\Services;

use Illuminate\Support\Facades\Config;

class CostCalculator
{
    /**
     * Calculate cost from token usage using provider config.
     */
    public function calculate(
        string $provider,
        string $model,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?int $totalTokens = null
    ): array {
        $providerConfig = Config::get("ai-metering.providers.{$provider}", []);

        if (! isset($providerConfig['models'][$model])) {
            return [
                'input_cost' => 0.0,
                'output_cost' => 0.0,
                'total_cost' => 0.0,
            ];
        }

        $modelConfig = $providerConfig['models'][$model];
        $inputPricePer1k = $modelConfig['input_price_per_1k'] ?? 0.0;
        $outputPricePer1k = $modelConfig['output_price_per_1k'] ?? 0.0;

        if ($inputTokens !== null && $outputTokens !== null) {
            $inputCost = ($inputTokens / 1000) * $inputPricePer1k;
            $outputCost = ($outputTokens / 1000) * $outputPricePer1k;

            return [
                'input_cost' => max(0.0, $inputCost),
                'output_cost' => max(0.0, $outputCost),
                'total_cost' => max(0.0, $inputCost + $outputCost),
            ];
        }

        // If we only have total tokens, split proportionally (50/50) or use average
        if ($totalTokens !== null) {
            $averagePricePer1k = ($inputPricePer1k + $outputPricePer1k) / 2;
            $totalCost = ($totalTokens / 1000) * $averagePricePer1k;

            $inputCost = ($totalTokens / 2 / 1000) * $inputPricePer1k;
            $outputCost = ($totalTokens / 2 / 1000) * $outputPricePer1k;

            return [
                'input_cost' => max(0.0, $inputCost),
                'output_cost' => max(0.0, $outputCost),
                'total_cost' => max(0.0, $totalCost),
            ];
        }

        return [
            'input_cost' => 0.0,
            'output_cost' => 0.0,
            'total_cost' => 0.0,
        ];
    }

    /**
     * Calculate cost from provider usage data.
     */
    public function calculateFromUsage(array $usage): array
    {
        if (isset($usage['input_cost']) || isset($usage['output_cost']) || isset($usage['total_cost'])) {
            return [
                'input_cost' => max(0.0, $usage['input_cost'] ?? 0.0),
                'output_cost' => max(0.0, $usage['output_cost'] ?? 0.0),
                'total_cost' => max(0.0, $usage['total_cost'] ?? ($usage['input_cost'] ?? 0.0) + ($usage['output_cost'] ?? 0.0)),
            ];
        }

        return $this->calculate(
            $usage['provider'] ?? 'manual',
            $usage['model'] ?? 'unknown',
            $usage['input_tokens'] ?? null,
            $usage['output_tokens'] ?? null,
            $usage['total_tokens'] ?? null
        );
    }
}
