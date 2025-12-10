<?php

namespace Ajooda\AiMetering\Support;

class LimitCheckResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly bool $approaching,
        public readonly bool $hardLimitReached,
        public readonly ?int $remainingTokens,
        public readonly ?float $remainingCost,
        public readonly float $usagePercentage = 0.0
    ) {}

    /**
     * Create a result indicating usage is allowed.
     */
    public static function allowed(?int $remainingTokens = null, ?float $remainingCost = null, float $usagePercentage = 0.0): self
    {
        return new self(
            allowed: true,
            approaching: $usagePercentage >= 80.0,
            hardLimitReached: false,
            remainingTokens: $remainingTokens,
            remainingCost: $remainingCost,
            usagePercentage: $usagePercentage
        );
    }

    /**
     * Create a result indicating hard limit is reached.
     */
    public static function limitReached(): self
    {
        return new self(
            allowed: false,
            approaching: true,
            hardLimitReached: true,
            remainingTokens: 0,
            remainingCost: 0.0,
            usagePercentage: 100.0
        );
    }

    /**
     * Create a result for unlimited plans.
     */
    public static function unlimited(): self
    {
        return new self(
            allowed: true,
            approaching: false,
            hardLimitReached: false,
            remainingTokens: null,
            remainingCost: null,
            usagePercentage: 0.0
        );
    }
}
