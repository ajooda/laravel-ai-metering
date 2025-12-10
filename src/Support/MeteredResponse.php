<?php

namespace Ajooda\AiMetering\Support;

class MeteredResponse
{
    public function __construct(
        public readonly mixed $response,
        public readonly ProviderUsage $usage,
        public readonly LimitCheckResult $limitCheck
    ) {}

    /**
     * Get the original provider response.
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }

    /**
     * Get the usage information.
     */
    public function getUsage(): ProviderUsage
    {
        return $this->usage;
    }

    /**
     * Get the limit check result.
     */
    public function getLimitCheck(): LimitCheckResult
    {
        return $this->limitCheck;
    }

    /**
     * Get remaining tokens.
     */
    public function getRemainingTokens(): ?int
    {
        return $this->limitCheck->remainingTokens;
    }

    /**
     * Get remaining cost.
     */
    public function getRemainingCost(): ?float
    {
        return $this->limitCheck->remainingCost;
    }

    /**
     * Check if usage is approaching limit.
     */
    public function isApproachingLimit(): bool
    {
        return $this->limitCheck->approaching;
    }

    /**
     * Check if limit is reached.
     */
    public function isLimitReached(): bool
    {
        return $this->limitCheck->hardLimitReached;
    }
}
