<?php

namespace Ajooda\AiMetering\Services;

use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Models\AiUsageLimitOverride;
use Ajooda\AiMetering\Support\LimitCheckResult;
use Ajooda\AiMetering\Support\Period;
use Illuminate\Support\Facades\Cache;

class UsageLimiter
{
    public function __construct(
        protected PlanResolver $planResolver
    ) {}

    /**
     * Check if usage is allowed for the given billable entity.
     */
    public function checkLimit(
        mixed $billable,
        ?int $requestedTokens = null,
        ?float $requestedCost = null
    ): LimitCheckResult {
        if (! $billable) {
            return LimitCheckResult::unlimited();
        }

        $subscription = $this->planResolver->resolveSubscription($billable);

        if (! $subscription || ! $subscription->isActive()) {
            return LimitCheckResult::unlimited();
        }

        $plan = $subscription->plan;

        if (! $plan || ! $plan->is_active) {
            return LimitCheckResult::unlimited();
        }

        $period = Period::fromConfig(config('ai-metering.period', []));
        $periodStart = $period->getStart();
        $periodEnd = $period->getEnd();

        $override = $this->getOverrideForPeriod($billable, $periodStart, $periodEnd);

        $usage = $this->getUsageForPeriod($billable, $periodStart, $periodEnd);

        $tokenLimit = $override?->token_limit ?? $plan->monthly_token_limit;
        $costLimit = $override?->cost_limit ?? $plan->monthly_cost_limit;

        if ($tokenLimit === null && $costLimit === null) {
            return LimitCheckResult::unlimited();
        }

        $usedTokens = $usage['tokens'] ?? 0;
        $usedCost = $usage['cost'] ?? 0.0;

        if ($tokenLimit !== null && $costLimit !== null) {
            $remainingTokens = max(0, $tokenLimit - $usedTokens);
            $tokenUsagePercentage = $tokenLimit > 0 ? ($usedTokens / $tokenLimit) * 100 : 0.0;

            if ($requestedTokens !== null && ($usedTokens + $requestedTokens) > $tokenLimit) {
                return LimitCheckResult::limitReached();
            }

            if ($remainingTokens <= 0) {
                return LimitCheckResult::limitReached();
            }

            $remainingCost = max(0.0, $costLimit - $usedCost);
            $costUsagePercentage = $costLimit > 0 ? ($usedCost / $costLimit) * 100 : 0.0;

            $usagePercentage = max($tokenUsagePercentage, $costUsagePercentage);

            if ($requestedCost !== null && ($usedCost + $requestedCost) > $costLimit) {
                return LimitCheckResult::limitReached();
            }

            if ($remainingCost <= 0) {
                return LimitCheckResult::limitReached();
            }

            return LimitCheckResult::allowed(
                remainingTokens: $remainingTokens,
                remainingCost: $remainingCost,
                usagePercentage: $usagePercentage
            );
        }

        if ($tokenLimit !== null) {
            $remainingTokens = max(0, $tokenLimit - $usedTokens);
            $usagePercentage = $tokenLimit > 0 ? ($usedTokens / $tokenLimit) * 100 : 0.0;

            if ($requestedTokens !== null && ($usedTokens + $requestedTokens) > $tokenLimit) {
                return LimitCheckResult::limitReached();
            }

            if ($remainingTokens <= 0) {
                return LimitCheckResult::limitReached();
            }

            return LimitCheckResult::allowed(
                remainingTokens: $remainingTokens,
                remainingCost: null,
                usagePercentage: $usagePercentage
            );
        }

        if ($costLimit !== null) {
            $remainingCost = max(0.0, $costLimit - $usedCost);
            $usagePercentage = $costLimit > 0 ? ($usedCost / $costLimit) * 100 : 0.0;

            if ($requestedCost !== null && ($usedCost + $requestedCost) > $costLimit) {
                return LimitCheckResult::limitReached();
            }

            if ($remainingCost <= 0) {
                return LimitCheckResult::limitReached();
            }

            return LimitCheckResult::allowed(
                remainingTokens: null,
                remainingCost: $remainingCost,
                usagePercentage: $usagePercentage
            );
        }

        return LimitCheckResult::unlimited();
    }

    /**
     * Get usage for the current period.
     */
    protected function getUsageForPeriod(mixed $billable, $periodStart, $periodEnd): array
    {
        $cacheKey = config('ai-metering.performance.cache_limit_checks', true)
            ? "ai-metering.usage.{$billable->getMorphClass()}.{$billable->id}.{$periodStart->timestamp}.{$periodEnd->timestamp}"
            : null;

        if ($cacheKey) {
            return Cache::remember($cacheKey, config('ai-metering.performance.cache_ttl', 300), function () use ($billable, $periodStart, $periodEnd) {
                return $this->calculateUsage($billable, $periodStart, $periodEnd);
            });
        }

        return $this->calculateUsage($billable, $periodStart, $periodEnd);
    }

    /**
     * Calculate usage for the period.
     */
    protected function calculateUsage(mixed $billable, $periodStart, $periodEnd): array
    {
        $usage = AiUsage::where('billable_type', get_class($billable))
            ->where('billable_id', $billable->id)
            ->where('occurred_at', '>=', $periodStart)
            ->where('occurred_at', '<', $periodEnd)
            ->selectRaw('SUM(total_tokens) as tokens, SUM(total_cost) as cost')
            ->first();

        return [
            'tokens' => (int) ($usage->tokens ?? 0),
            'cost' => (float) ($usage->cost ?? 0.0),
        ];
    }

    /**
     * Get override for the period.
     */
    protected function getOverrideForPeriod(mixed $billable, $periodStart, $periodEnd): ?AiUsageLimitOverride
    {
        return AiUsageLimitOverride::where('billable_type', get_class($billable))
            ->where('billable_id', $billable->id)
            ->where('period_start', '<=', $periodStart)
            ->where('period_end', '>=', $periodEnd)
            ->first();
    }

    /**
     * Clear usage cache for a billable entity.
     */
    public function clearCache(mixed $billable): void
    {
        if (! $billable) {
            return;
        }

        $period = Period::fromConfig(config('ai-metering.period', []));
        $periodStart = $period->getStart();
        $periodEnd = $period->getEnd();

        $cacheKey = "ai-metering.usage.{$billable->getMorphClass()}.{$billable->id}.{$periodStart->timestamp}.{$periodEnd->timestamp}";
        Cache::forget($cacheKey);
    }
}
