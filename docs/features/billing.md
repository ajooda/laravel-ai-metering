# Billing Integration

Laravel AI Metering integrates with Laravel Cashier for Stripe billing. This guide covers setting up Stripe integration, handling webhooks, and managing overages.

## Stripe/Cashier Integration

### Installation

Install Laravel Cashier:

```bash
composer require laravel/cashier
```

### Configure Billable Model

Add the `Billable` trait to your User model (or other billable models):

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

### Configure Billing Driver

Set the billing driver in `config/ai-metering.php`:

```php
'billing' => [
    'driver' => \Ajooda\AiMetering\Services\Billing\CashierBillingDriver::class,
],
```

Or via environment variable:

```env
AI_METERING_BILLING_DRIVER=Ajooda\AiMetering\Services\Billing\CashierBillingDriver
```

## Auto Sync Stripe IDs

The package automatically syncs Stripe subscription and customer IDs to `AiSubscription.meta` when creating/updating subscriptions. This enables webhook handling to work automatically.

This is enabled by default. To disable:

```php
'auto_sync_stripe_ids' => env('AI_METERING_AUTO_SYNC_STRIPE_IDS', false),
```

### Manual Stripe ID Setup

If auto-sync is disabled, set Stripe IDs manually:

```php
use Ajooda\AiMetering\Models\AiSubscription;

$subscription = AiSubscription::create([
    'billable_type' => User::class,
    'billable_id' => $user->id,
    'ai_plan_id' => $plan->id,
    'billing_mode' => 'plan',
    'started_at' => now(),
    'renews_at' => now()->addMonth(),
    'meta' => [
        'stripe_subscription_id' => $stripeSubscription->stripe_id,
        'stripe_customer_id' => $user->stripe_id,
    ],
]);
```

## Webhook Handling

The package automatically handles Stripe webhooks via Laravel Cashier's webhook system.

### Webhook Events Handled

- `customer.subscription.deleted` - Handles subscription cancellations
- `customer.subscription.updated` - Handles subscription updates (renewals, plan changes, reactivations)
- `invoice.payment_failed` - Handles payment failures with grace period support

### Webhook Setup

1. Configure Cashier webhooks in your Stripe dashboard
2. Add webhook endpoint to your routes (Cashier handles this)
3. The package automatically listens to Cashier webhook events

No additional setup required! The webhook handler (`HandleCashierWebhooks`) is automatically registered when Cashier is detected.

## Overage Charges

When usage exceeds plan limits and `overage_behavior` is set to `charge`, overages are recorded in the `ai_overages` table.

### Overage Sync Strategy

Configure how overages are synced to Stripe:

```php
'overage_sync_strategy' => env('AI_METERING_OVERAGE_SYNC_STRATEGY', 'batch'), // 'immediate', 'batch'
```

- `immediate`: Sync immediately (may impact performance)
- `batch`: Sync in batches via command (recommended for high volume)

### Syncing Overages

For batch sync strategy, run the sync command:

```bash
php artisan ai-metering:sync-stripe-overages
php artisan ai-metering:sync-stripe-overages --limit=50
```

This command:
1. Finds unsynced overages
2. Creates Stripe invoice items or charges
3. Marks overages as synced

### Overage Model

```php
use Ajooda\AiMetering\Models\AiOverage;

// Query overages
$overages = AiOverage::where('billable_id', $user->id)
    ->where('is_synced', false)
    ->get();

// Mark as synced
$overage->markAsSynced($stripeInvoiceItemId);
```

## Payment Failure Grace Period

Configure grace period after payment failures:

```php
'payment_failure_grace_period_days' => env('AI_METERING_PAYMENT_FAILURE_GRACE_PERIOD', 7),
```

During grace period:
- Subscription remains active
- Limits still apply
- Usage is tracked

After grace period expires:
- Subscription is considered expired
- No limits enforced (access control is your responsibility)

## Currency Configuration

Set default currency:

```php
'currency' => env('AI_METERING_CURRENCY', 'usd'),
```

### Currency Conversion Rates

Define conversion rates between currencies:

```php
'currency_rates' => [
    'usd_eur' => 0.85,  // 1 USD = 0.85 EUR
    'eur_usd' => 1.18,  // 1 EUR = 1.18 USD
],
```

Update these rates regularly or integrate with a currency API.

## Billing Modes

### Plan-Based Billing

Usage is tracked against plan limits. Overage charges can be applied:

```php
$response = AiMeter::forUser($user)
    ->billable($user)
    ->billingMode('plan')
    ->usingProvider('openai', 'gpt-4o-mini')
    ->call(fn () => OpenAI::chat()->create([...]));
```

### Credit-Based Billing

Usage is deducted from credit wallet:

```php
$response = AiMeter::forUser($user)
    ->billable($user)
    ->billingMode('credits')
    ->usingProvider('openai', 'gpt-4o-mini')
    ->call(fn () => OpenAI::chat()->create([...]));
```

See [Credits](credits.md) for more details.

## Events

Listen to billing events:

```php
use Ajooda\AiMetering\Events\AiOverageCharged;
use Ajooda\AiMetering\Events\AiSubscriptionExpired;

Event::listen(AiOverageCharged::class, function ($event) {
    // Handle overage charge
    $overage = $event->overage;
    $amount = $event->amount;
});

Event::listen(AiSubscriptionExpired::class, function ($event) {
    // Handle subscription expiration
    $subscription = $event->subscription;
});
```

## Best Practices

1. **Use batch sync for high volume**: Use `batch` strategy for overage sync in production
2. **Monitor webhook delivery**: Ensure webhooks are being received and processed
3. **Handle payment failures gracefully**: Notify users and provide renewal options
4. **Track overages**: Monitor overage charges and usage patterns
5. **Set up alerts**: Alert on payment failures and subscription expirations

## Troubleshooting

### Webhooks Not Working

1. Verify Stripe IDs are synced to `AiSubscription.meta`
2. Check Cashier webhook configuration
3. Review webhook logs in Stripe dashboard
4. Ensure `auto_sync_stripe_ids` is enabled (or set IDs manually)

### Overages Not Syncing

1. Check `overage_sync_strategy` configuration
2. Run `ai-metering:sync-stripe-overages` command
3. Verify Stripe API keys are configured
4. Check for errors in logs

## Next Steps

- [Plans & Quotas](plans-and-quotas.md) - Set up usage limits
- [Credits](credits.md) - Credit-based billing
- [Webhook Handling](../advanced.md#webhook-handling) - Advanced webhook configuration

