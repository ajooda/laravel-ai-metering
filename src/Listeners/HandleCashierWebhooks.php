<?php

namespace Ajooda\AiMetering\Listeners;

use Ajooda\AiMetering\Events\AiSubscriptionExpired;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Services\PlanResolver;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleCashierWebhooks
{
    public function __construct(
        protected PlanResolver $planResolver
    ) {}

    /**
     * Handle the webhook event.
     */
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? null;

        if (! $type) {
            return;
        }

        match ($type) {
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($payload),
            'invoice.payment_failed' => $this->handlePaymentFailed($payload),
            default => null,
        };
    }

    /**
     * Handle subscription deletion (cancellation).
     */
    protected function handleSubscriptionDeleted(array $payload): void
    {
        $stripeSubscription = $payload['data']['object'] ?? null;

        if (! $stripeSubscription) {
            return;
        }

        $stripeId = $stripeSubscription['id'] ?? null;
        if (! $stripeId) {
            return;
        }

        $subscription = $this->findSubscriptionByStripeId($stripeId);

        if (! $subscription) {
            if (config('ai-metering.logging.enabled', true)) {
                Log::warning('AiSubscription not found for Stripe subscription', [
                    'stripe_subscription_id' => $stripeId,
                ]);
            }

            return;
        }

        $canceledAt = $stripeSubscription['canceled_at'] ?? null;
        $currentPeriodEnd = $stripeSubscription['current_period_end'] ?? null;

        $endsAt = $canceledAt ? \Carbon\Carbon::createFromTimestamp($canceledAt) : null;
        if (! $endsAt && $currentPeriodEnd) {
            $endsAt = \Carbon\Carbon::createFromTimestamp($currentPeriodEnd);
        }

        $subscription->update([
            'ends_at' => $endsAt,
        ]);

        if ($subscription->billable) {
            $this->planResolver->clearCache($subscription->billable);
        }

        event(new AiSubscriptionExpired($subscription));

        if (config('ai-metering.logging.enabled', true)) {
            Log::info('AiSubscription cancelled via webhook', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeId,
                'ends_at' => $endsAt,
            ]);
        }
    }

    /**
     * Handle subscription update (plan change, renewal, etc.).
     */
    protected function handleSubscriptionUpdated(array $payload): void
    {
        $stripeSubscription = $payload['data']['object'] ?? null;

        if (! $stripeSubscription) {
            return;
        }

        $stripeId = $stripeSubscription['id'] ?? null;
        if (! $stripeId) {
            return;
        }

        $subscription = $this->findSubscriptionByStripeId($stripeId);

        if (! $subscription) {
            if (config('ai-metering.logging.enabled', true)) {
                Log::warning('AiSubscription not found for Stripe subscription update', [
                    'stripe_subscription_id' => $stripeId,
                ]);
            }

            return;
        }

        $currentPeriodEnd = $stripeSubscription['current_period_end'] ?? null;
        $currentPeriodStart = $stripeSubscription['current_period_start'] ?? null;
        $canceledAt = $stripeSubscription['canceled_at'] ?? null;
        $status = $stripeSubscription['status'] ?? null;

        $updates = [];

        if ($currentPeriodEnd) {
            $updates['renews_at'] = \Carbon\Carbon::createFromTimestamp($currentPeriodEnd);
        }

        if ($currentPeriodStart) {
            $updates['started_at'] = \Carbon\Carbon::createFromTimestamp($currentPeriodStart);
        }

        if ($canceledAt) {
            $updates['ends_at'] = \Carbon\Carbon::createFromTimestamp($canceledAt);
        } elseif ($status === 'active' && $subscription->ends_at) {
            $updates['ends_at'] = null;
        }

        if (! empty($updates)) {
            $subscription->update($updates);

            if ($subscription->billable) {
                $this->planResolver->clearCache($subscription->billable);
            }

            if (config('ai-metering.logging.enabled', true)) {
                Log::info('AiSubscription updated via webhook', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeId,
                    'updates' => $updates,
                ]);
            }
        }
    }

    /**
     * Handle payment failure.
     */
    protected function handlePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'] ?? null;

        if (! $invoice) {
            return;
        }

        $customerId = $invoice['customer'] ?? null;
        if (! $customerId) {
            return;
        }

        $subscription = $this->findSubscriptionByStripeCustomerId($customerId);

        if (! $subscription) {
            if (config('ai-metering.logging.enabled', true)) {
                Log::warning('AiSubscription not found for payment failure', [
                    'stripe_customer_id' => $customerId,
                ]);
            }

            return;
        }

        $gracePeriodDays = config('ai-metering.billing.payment_failure_grace_period_days', 7);

        if ($gracePeriodDays > 0) {
            $subscription->update([
                'grace_period_ends_at' => now()->addDays($gracePeriodDays),
            ]);
        } else {
            $subscription->update([
                'ends_at' => now(),
                'grace_period_ends_at' => null,
            ]);
        }

        if ($subscription->billable) {
            $this->planResolver->clearCache($subscription->billable);
        }

        if (config('ai-metering.logging.enabled', true)) {
            Log::warning('AiSubscription payment failed', [
                'subscription_id' => $subscription->id,
                'stripe_customer_id' => $customerId,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
            ]);
        }
    }

    /**
     * Find subscription by Stripe subscription ID.
     */
    protected function findSubscriptionByStripeId(string $stripeId): ?AiSubscription
    {
        $subscription = AiSubscription::whereJsonContains('meta->stripe_subscription_id', $stripeId)->first();

        if ($subscription) {
            return $subscription;
        }

        if (config('ai-metering.logging.enabled', true) && config('ai-metering.logging.level') === 'debug') {
            Log::debug('Could not find AiSubscription by Stripe ID', [
                'stripe_subscription_id' => $stripeId,
                'hint' => 'Store stripe_subscription_id in subscription meta when creating subscription',
            ]);
        }

        return null;
    }

    /**
     * Find subscription by Stripe customer ID.
     */
    protected function findSubscriptionByStripeCustomerId(string $customerId): ?AiSubscription
    {
        $subscription = AiSubscription::whereJsonContains('meta->stripe_customer_id', $customerId)->first();

        if ($subscription) {
            return $subscription;
        }

        if (config('ai-metering.logging.enabled', true) && config('ai-metering.logging.level') === 'debug') {
            Log::debug('Could not find AiSubscription by Stripe customer ID', [
                'stripe_customer_id' => $customerId,
                'hint' => 'Store stripe_customer_id in subscription meta when creating subscription',
            ]);
        }

        return null;
    }
}
