# Advanced Topics

This guide covers advanced usage patterns, extensibility, customization, and advanced features.

## Extending Providers

Create custom provider implementations for AI providers not supported out of the box.

### Provider Interface

Implement the `ProviderClient` interface:

```php
namespace App\Providers;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Support\ProviderUsage;

class CustomProvider implements ProviderClient
{
    public function call(callable $callback): array
    {
        $response = $callback();
        
        // Extract usage from response
        $usage = new ProviderUsage(
            inputTokens: $response->input_tokens ?? 0,
            outputTokens: $response->output_tokens ?? 0,
            totalTokens: $response->total_tokens ?? 0,
            inputCost: $this->calculateInputCost($response),
            outputCost: $this->calculateOutputCost($response),
            totalCost: $this->calculateTotalCost($response),
            currency: 'usd',
        );
        
        return [
            'response' => $response,
            'usage' => $usage,
        ];
    }
    
    protected function calculateInputCost($response): float
    {
        // Calculate input cost based on your pricing model
        return ($response->input_tokens / 1000) * 0.001;
    }
    
    protected function calculateOutputCost($response): float
    {
        // Calculate output cost based on your pricing model
        return ($response->output_tokens / 1000) * 0.002;
    }
    
    protected function calculateTotalCost($response): float
    {
        return $this->calculateInputCost($response) + $this->calculateOutputCost($response);
    }
}
```

### Register Provider

Register in `config/ai-metering.php`:

```php
'providers' => [
    'custom' => [
        'class' => \App\Providers\CustomProvider::class,
        'models' => [
            'custom-model' => [
                'input_price_per_1k' => 0.001,
                'output_price_per_1k' => 0.002,
            ],
        ],
    ],
],
```

## Events

Listen to package events for lifecycle hooks:

### Available Events

- `AiUsageRecorded` - When usage is recorded
- `AiLimitApproaching` - When usage exceeds 80% of limit
- `AiLimitReached` - When hard limit is reached
- `AiProviderCallFailed` - When provider call fails
- `AiOverageCharged` - When overage is charged
- `AiPlanChanged` - When plan changes
- `AiCreditsAdded` - When credits are added
- `AiCreditsDeducted` - When credits are deducted
- `AiSubscriptionExpired` - When subscription expires

### Event Listeners

```php
use Ajooda\AiMetering\Events\AiUsageRecorded;
use Ajooda\AiMetering\Events\AiLimitApproaching;
use Ajooda\AiMetering\Events\AiLimitReached;

Event::listen(AiUsageRecorded::class, function ($event) {
    // Handle usage recorded
    $usage = $event->usage;
    $billable = $event->billable;
    
    // Send notification, update analytics, etc.
});

Event::listen(AiLimitApproaching::class, function ($event) {
    // Send notification when approaching limit
    $billable = $event->billable;
    $usagePercentage = $event->usagePercentage;
    
    // Notify user: "You've used 85% of your monthly limit"
});

Event::listen(AiLimitReached::class, function ($event) {
    // Handle limit reached
    $billable = $event->billable;
    
    // Block access, send alert, etc.
});
```

### Event Payloads

Each event contains relevant data. Check the event class for available properties:

```php
// AiUsageRecorded
$event->usage;        // AiUsage model
$event->billable;     // Billable entity
$event->providerUsage; // ProviderUsage object

// AiLimitApproaching
$event->billable;         // Billable entity
$event->usagePercentage;  // float (0-100)
$event->remainingTokens;  // ?int
$event->remainingCost;    // ?float

// AiLimitReached
$event->billable;         // Billable entity
$event->limitType;        // 'tokens' or 'cost'
```

## Custom Billing Drivers

Create custom billing drivers for payment systems other than Stripe:

### Billing Driver Interface

Implement the `BillingDriver` interface:

```php
namespace App\Billing;

use Ajooda\AiMetering\Services\Billing\BillingDriver;
use Ajooda\AiMetering\Models\AiOverage;

class CustomBillingDriver extends BillingDriver
{
    public function chargeOverage(AiOverage $overage): bool
    {
        // Charge overage via your payment system
        $charge = $this->paymentGateway->charge(
            $overage->billable,
            $overage->amount,
            $overage->currency
        );
        
        if ($charge->successful) {
            $overage->markAsSynced($charge->id);
            return true;
        }
        
        return false;
    }
    
    public function refundOverage(AiOverage $overage): bool
    {
        // Refund overage via your payment system
        return $this->paymentGateway->refund($overage->sync_id);
    }
}
```

### Register Billing Driver

Register in `config/ai-metering.php`:

```php
'billing' => [
    'driver' => \App\Billing\CustomBillingDriver::class,
],
```

## Performance Optimization

### Caching

Limit checks are cached by default:

```php
'performance' => [
    'cache_limit_checks' => true,
    'cache_ttl' => 300, // seconds
],
```

### Queue Support

Record usage asynchronously for better performance:

```php
'performance' => [
    'queue_usage_recording' => 'default', // Queue name or false
],
```

> **Important**: If queue recording is enabled, ensure your queue worker is running:
> ```bash
> php artisan queue:work
> ```

### Batch Recording

Record multiple usages efficiently:

```php
use Ajooda\AiMetering\Services\UsageRecorder;

$recorder = app(UsageRecorder::class);

$usages = [
    [
        'billable_type' => User::class,
        'billable_id' => $user->id,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'total_tokens' => 100,
        'total_cost' => 0.1,
        'occurred_at' => now(),
    ],
    // ... more usage records
];

$recorded = $recorder->recordBatch($usages);
```

## Security Best Practices

### Input Validation

The package validates:
- Feature names (alphanumeric + underscore)
- Token/cost values (non-negative)
- Metadata sanitization

Always validate user input before passing to `AiMeter`:

```php
$validated = $request->validate([
    'feature' => 'required|string|max:100|regex:/^[a-zA-Z0-9_]+$/',
]);
```

### Data Privacy (GDPR)

Delete all usage for a user:

```php
use Ajooda\AiMetering\Models\AiUsage;

// Delete all usage records
AiUsage::where('billable_type', User::class)
    ->where('billable_id', $user->id)
    ->delete();

// Or delete by user_id
AiUsage::where('user_id', $user->id)->delete();
```

Anonymize old usage data:

```php
AiUsage::where('occurred_at', '<', now()->subYears(2))
    ->update([
        'billable_type' => null,
        'billable_id' => null,
        'user_id' => null,
        'meta' => null,
    ]);
```

## Webhook Handling

### Stripe Webhooks

The package integrates with Laravel Cashier's webhook handling and automatically processes webhooks for subscription lifecycle events.

**Automatic Stripe ID Syncing**: When you create an `AiSubscription` and Laravel Cashier is installed, the package automatically syncs Stripe subscription and customer IDs to the `meta` field. This enables webhook handling to work out of the box.

**Webhook Events Handled**:
- `customer.subscription.deleted` - Handles subscription cancellations
- `customer.subscription.updated` - Handles subscription updates (renewals, plan changes, reactivations)
- `invoice.payment_failed` - Handles payment failures with grace period support

**Configuration**:
```php
// Disable automatic Stripe ID syncing (if needed)
'auto_sync_stripe_ids' => false, // Default: true
```

The webhook handler (`HandleCashierWebhooks`) is automatically registered when Cashier is detected. No additional setup required!

## Error Handling

The package throws specific exceptions:

```php
use Ajooda\AiMetering\Exceptions\AiLimitExceededException;
use Ajooda\AiMetering\Exceptions\AiCreditsInsufficientException;
use Ajooda\AiMetering\Exceptions\AiProviderException;
use Ajooda\AiMetering\Exceptions\AiBillingException;

try {
    $response = AiMeter::forUser($user)->call(fn () => OpenAI::chat()->create([...]));
} catch (AiLimitExceededException $e) {
    // Handle limit exceeded
} catch (AiCreditsInsufficientException $e) {
    // Handle insufficient credits
} catch (AiProviderException $e) {
    // Handle provider errors
} catch (AiBillingException $e) {
    // Handle billing errors
}
```

## Artisan Commands

### Usage Report

```bash
php artisan ai-metering:report
php artisan ai-metering:report --month=2024-01
php artisan ai-metering:report --billable-type="App\Models\User" --billable=1
```

### Cleanup Old Usage

```bash
php artisan ai-metering:cleanup
php artisan ai-metering:cleanup --days=90
php artisan ai-metering:cleanup --dry-run
```

### Sync Stripe Overages

```bash
php artisan ai-metering:sync-stripe-overages
php artisan ai-metering:sync-stripe-overages --limit=50
```

### Validate Configuration

```bash
php artisan ai-metering:validate
```

### Migrate Plans

```bash
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan"
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan" --from-plan="basic-plan"
```

### Sync Plans

```bash
php artisan ai-metering:sync-plans
```

## Next Steps

- [API Reference](api-reference.md) - Complete API documentation
- [Troubleshooting](troubleshooting.md) - Common issues and solutions
- [Features](../features/) - Detailed feature documentation

