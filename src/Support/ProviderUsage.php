<?php

namespace Ajooda\AiMetering\Support;

class ProviderUsage
{
    public function __construct(
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $totalTokens = null,
        public ?float $inputCost = null,
        public ?float $outputCost = null,
        public ?float $totalCost = null,
        public ?string $currency = null
    ) {}

    /**
     * Convert to array format for usage recording.
     */
    public function toArray(): array
    {
        return [
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'total_tokens' => $this->totalTokens,
            'input_cost' => $this->inputCost,
            'output_cost' => $this->outputCost,
            'total_cost' => $this->totalCost,
            'currency' => $this->currency ?? 'usd',
        ];
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            inputTokens: $data['input_tokens'] ?? null,
            outputTokens: $data['output_tokens'] ?? null,
            totalTokens: $data['total_tokens'] ?? null,
            inputCost: $data['input_cost'] ?? null,
            outputCost: $data['output_cost'] ?? null,
            totalCost: $data['total_cost'] ?? null,
            currency: $data['currency'] ?? 'usd'
        );
    }
}
