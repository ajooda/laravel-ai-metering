# Plans & Quotas

Plans define usage limits (tokens and/or cost) that are enforced for subscribers. This guide covers creating plans, managing subscriptions, and configuring quotas.

## Creating Plans

Plans define usage limits and features:

```php
use Ajooda\AiMetering\Models\AiPlan;

$plan = AiPlan::create([
    'name' => 'Pro Plan',
    'slug' => 'pro',
    'monthly_token_limit' => 1000000,        // Token limit (null = unlimited)
    'monthly_cost_limit' => 100.00,          // Cost limit (null = unlimited)
    'overage_price_per_1k_tokens' => 0.01,   // Overage pricing (null = no overages)
    'features' => ['feature1', 'feature2'],   // Optional feature flags
    'trial_days' => 14,                       // Optional trial period
    'is_active' => true,
]);
```

### Plan Configuration Options

- `name`: Display name for the plan
- `slug`: Unique identifier (used in URLs, etc.)
- `monthly_token_limit`: Maximum tokens per period (null = unlimited)
- `monthly_cost_limit`: Maximum cost per period (null = unlimited)
- `overage_price_per_1k_tokens`: Price per 1k tokens for overages (null = no overages)
- `features`: Array of feature flags (optional)
- `trial_days`: Trial period in days (optional)
- `is_active`: Whether the plan is active

### Plan Methods

```php
$plan->hasUnlimitedTokens();  // bool - Check if tokens are unlimited
$plan->hasUnlimitedCost();    // bool - Check if cost is unlimited
$plan->allowsOverage();       // bool - Check if overages are allowed
```

## Creating Subscriptions

Subscriptions link billable entities to plans:

```php
use Ajooda\AiMetering\Models\AiSubscription;

$subscription = AiSubscription::create([
    'billable_type' => User::class,
    'billable_id' => $user->id,
    'ai_plan_id' => $plan->id,
    'billing_mode' => 'plan', // or 'credits'
    'started_at' => now(),
    'renews_at' => now()->addMonth(),
    'trial_ends_at' => now()->addDays(14),      // Optional trial period
    'grace_period_ends_at' => null,              // Optional grace period
]);
```

### Subscription Options

- `billable_type`: Eloquent model class (User, Tenant, etc.)
- `billable_id`: ID of the billable entity
- `ai_plan_id`: Plan ID
- `billing_mode`: `'plan'` or `'credits'`
- `started_at`: Subscription start date
- `renews_at`: Next renewal date
- `trial_ends_at`: Trial end date (optional)
- `grace_period_ends_at`: Grace period end date (optional)

### Subscription Status

```php
$subscription->isActive();        // bool - Is subscription active?
$subscription->isExpired();       // bool - Has subscription expired?
$subscription->isInTrial();       // bool - Is subscription in trial?
$subscription->isInGracePeriod(); // bool - Is subscription in grace period?
```

### Subscription Relationships

```php
$subscription->plan;          // BelongsTo AiPlan
$subscription->previousPlan;  // BelongsTo AiPlan (previous plan)
$subscription->billable;      // MorphTo (User, Tenant, etc.)
```

## Limit Overrides

Override limits for specific periods:

```php
use Ajooda\AiMetering\Models\AiUsageLimitOverride;

AiUsageLimitOverride::create([
    'billable_type' => User::class,
    'billable_id' => $user->id,
    'period_start' => now()->startOfMonth(),
    'period_end' => now()->endOfMonth(),
    'token_limit' => 2000000, // Double the plan limit
    'cost_limit' => 200.00,   // Optional cost limit override
]);
```

Overrides take precedence over plan limits for the specified period.

## Plan Lifecycle

### Plan Changes

When a user changes plans mid-period:

```php
$subscription = AiSubscription::where('billable_id', $user->id)->first();

// Update to new plan
$subscription->update([
    'ai_plan_id' => $newPlan->id,
    'previous_plan_id' => $oldPlan->id, // Track previous plan
]);

// Usage before plan change counts against old plan
// Usage after plan change counts against new plan
```

### Subscription Expiration

> ⚠️ **Important**: This package is a **metering and billing library, not an access control library**.

When a subscription expires:

1. **No Limits Enforced**: Expired subscriptions are treated the same as having no subscription. No limits are enforced.
2. **Usage Still Tracked**: Usage is still recorded for analytics.
3. **No Billing**: No charges are applied.
4. **Access Control is Your Responsibility**: The library does **not** block AI calls for expired subscriptions.

To block access when subscriptions expire, add checks in your application:

**Option 1: Middleware**

```php
// app/Http/Middleware/CheckAiSubscription.php
use Ajooda\AiMetering\Services\PlanResolver;

public function handle($request, Closure $next)
{
    $user = auth()->user();
    $planResolver = app(PlanResolver::class);
    $subscription = $planResolver->resolveSubscription($user);
    
    if (!$subscription) {
        return response()->json([
            'error' => 'No active subscription',
            'message' => 'Please subscribe to continue using AI features.'
        ], 403);
    }
    
    return $next($request);
}
```

**Option 2: Controller Check**

```php
use Ajooda\AiMetering\Services\PlanResolver;

public function generateContent(Request $request)
{
    $planResolver = app(PlanResolver::class);
    $subscription = $planResolver->resolveSubscription(auth()->user());
    
    if (!$subscription) {
        return redirect()->route('subscription.renew')
            ->with('error', 'Your subscription has expired.');
    }
    
    // Proceed with AI call
    $response = AiMeter::forUser(auth()->user())->call(...);
}
```

### Grace Periods

Configure grace periods in subscriptions:

```php
$subscription = AiSubscription::create([
    'billable_type' => User::class,
    'billable_id' => $user->id,
    'ai_plan_id' => $plan->id,
    'ends_at' => now()->subDay(), // Expired yesterday
    'grace_period_ends_at' => now()->addDays(7), // Grace period for 7 days
]);
```

During grace period, the subscription is still considered active and limits apply.

## Overage Behavior

Configure overage behavior in `config/ai-metering.php`:

```php
'overage_behavior' => 'block', // 'block', 'charge', 'allow'
```

- `block`: Block usage when limit is exceeded (throws `AiLimitExceededException`)
- `charge`: Allow usage and charge for overages
- `allow`: Allow usage without charging (track only)

When `charge` is enabled, overages are recorded in the `ai_overages` table and can be synced to Stripe.

## Artisan Commands

### List Plans

```bash
php artisan ai-metering:sync-plans
```

Lists all active plans in the system.

### Migrate Plan

```bash
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan"
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan" --from-plan="basic-plan"
```

Migrates a billable entity to a new plan.

## Next Steps

- [Credits](credits.md) - Credit-based billing
- [Billing Integration](billing.md) - Stripe/Cashier integration
- [Period Configuration](periods.md) - Configure usage periods
- [Advanced Topics](../advanced.md) - Advanced usage patterns

