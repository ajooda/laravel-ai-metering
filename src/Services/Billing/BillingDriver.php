<?php

namespace Ajooda\AiMetering\Services\Billing;

use Ajooda\AiMetering\Support\LimitCheckResult;

interface BillingDriver
{
    /**
     * Handle usage billing
     */
    public function handleUsage(
        mixed $billable,
        float $cost,
        int $tokens,
        LimitCheckResult $limitResult,
        string $currency = 'usd'
    ): void;

    /**
     * Handle refund for a billable entity.
     */
    public function handleRefund(
        mixed $billable,
        float $amount,
        string $reason
    ): void;
}
