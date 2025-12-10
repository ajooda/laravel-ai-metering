<?php

namespace Ajooda\AiMetering\Observers;

use Ajooda\AiMetering\Models\AiSubscription;
use Illuminate\Support\Facades\Log;

class AutoSyncStripeIdsObserver
{
    /**
     * Handle the AiSubscription "creating" event.
     */
    public function creating(AiSubscription $subscription): void
    {
        $this->syncStripeIds($subscription);
    }

    /**
     * Handle the AiSubscription "updating" event.
     */
    public function updating(AiSubscription $subscription): void
    {
        if (! $this->hasStripeIds($subscription)) {
            $this->syncStripeIds($subscription);
        }
    }

    /**
     * Sync Stripe IDs from Cashier subscription to AiSubscription meta.
     */
    protected function syncStripeIds(AiSubscription $subscription): void
    {
        if (! config('ai-metering.auto_sync_stripe_ids', true)) {
            return;
        }

        if (! class_exists(\Laravel\Cashier\Subscription::class)) {
            return;
        }

        $billable = $subscription->billable;
        if (! $billable) {
            if ($subscription->billable_type && $subscription->billable_id) {
                $billable = $subscription->billable_type::find($subscription->billable_id);
            }

            if (! $billable) {
                return;
            }
        }

        if (! method_exists($billable, 'subscriptions')) {
            return;
        }

        try {
            $stripeSubscription = $this->findStripeSubscription($billable);

            if (! $stripeSubscription) {
                return;
            }

            $stripeSubscriptionId = $stripeSubscription->stripe_id ?? null;
            $stripeCustomerId = $billable->stripe_id ?? null;

            if (! $stripeSubscriptionId) {
                return;
            }

            $meta = $subscription->meta ?? [];

            if (! isset($meta['stripe_subscription_id'])) {
                $meta['stripe_subscription_id'] = $stripeSubscriptionId;
            }

            if ($stripeCustomerId && ! isset($meta['stripe_customer_id'])) {
                $meta['stripe_customer_id'] = $stripeCustomerId;
            }

            $subscription->meta = $meta;

            if (config('ai-metering.logging.enabled', true)
                && config('ai-metering.logging.level') === 'debug') {
                Log::debug('Auto-synced Stripe IDs to AiSubscription', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscriptionId,
                    'stripe_customer_id' => $stripeCustomerId,
                ]);
            }
        } catch (\Exception $e) {
            if (config('ai-metering.logging.enabled', true)
                && config('ai-metering.logging.log_failures', true)) {
                Log::warning('Failed to auto-sync Stripe IDs', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Find the most appropriate Stripe subscription for the billable.
     *
     * @param  mixed  $billable
     * @return mixed|null
     */
    protected function findStripeSubscription($billable)
    {
        try {
            $subscriptions = $billable->subscriptions();

            $activeSubscription = $subscriptions
                ->where('stripe_status', 'active')
                ->latest('created_at')
                ->first();

            if ($activeSubscription) {
                return $activeSubscription;
            }

            return $subscriptions
                ->latest('created_at')
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if subscription already has Stripe IDs set.
     */
    protected function hasStripeIds(AiSubscription $subscription): bool
    {
        $meta = $subscription->meta ?? [];

        if (isset($meta['stripe_subscription_id'])) {
            return true;
        }

        $originalMeta = $subscription->getOriginal('meta');
        if (is_array($originalMeta) && isset($originalMeta['stripe_subscription_id'])) {
            return true;
        }

        return false;
    }
}
