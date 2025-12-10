<?php

namespace Ajooda\AiMetering\Services\Billing;

use Ajooda\AiMetering\Support\LimitCheckResult;

/**
 * Null billing driver that does nothing (for internal tracking only).
 */
class NullBillingDriver implements BillingDriver
{
    /**
     * Handle usage billing - no-op for null driver.
     */
    public function handleUsage(
        mixed $billable,
        float $cost,
        int $tokens,
        LimitCheckResult $limitResult,
        string $currency = 'usd'
    ): void {}

    /**
     * Handle refund - no-op for null driver.
     */
    public function handleRefund(
        mixed $billable,
        float $amount,
        string $reason
    ): void {}
}
