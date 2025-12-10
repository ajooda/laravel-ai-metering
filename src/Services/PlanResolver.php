<?php

namespace Ajooda\AiMetering\Services;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Illuminate\Support\Facades\Cache;

class PlanResolver
{
    /**
     * Resolve the active subscription for a billable entity.
     */
    public function resolveSubscription(mixed $billable): ?AiSubscription
    {
        if (! $billable) {
            return null;
        }

        $cacheKey = "ai-metering.subscription.{$billable->getMorphClass()}.{$billable->id}";

        return Cache::remember($cacheKey, config('ai-metering.performance.cache_ttl', 300), function () use ($billable) {
            return AiSubscription::where('billable_type', get_class($billable))
                ->where('billable_id', $billable->id)
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now())
                        ->orWhere(function ($q) {
                            $q->whereNotNull('grace_period_ends_at')
                                ->where('grace_period_ends_at', '>', now());
                        });
                })
                ->orderBy('started_at', 'desc')
                ->first();
        });
    }

    /**
     * Resolve the active plan for a billable entity.
     */
    public function resolvePlan(mixed $billable): ?AiPlan
    {
        $subscription = $this->resolveSubscription($billable);

        if (! $subscription || ! $subscription->isActive()) {
            return null;
        }

        return $subscription->plan;
    }

    /**
     * Get the billing mode for a billable entity.
     */
    public function getBillingMode(mixed $billable): ?string
    {
        $subscription = $this->resolveSubscription($billable);

        return $subscription?->billing_mode;
    }

    /**
     * Clear cache for a billable entity.
     */
    public function clearCache(mixed $billable): void
    {
        if (! $billable) {
            return;
        }

        $cacheKey = "ai-metering.subscription.{$billable->getMorphClass()}.{$billable->id}";
        Cache::forget($cacheKey);
    }
}
