# API Reference

Complete API reference for Laravel AI Metering.

## AiMeter Facade

The main facade for metering AI usage.

### Methods

#### `forUser(mixed $user): self`

Set the user for usage tracking.

```php
AiMeter::forUser($user)
```

#### `forTenant(mixed $tenant): self`

Set the tenant for multi-tenant applications.

```php
AiMeter::forTenant($tenant)
```

#### `billable(mixed $billable): self`

Set the billable entity (user, tenant, organization, etc.).

```php
AiMeter::billable($user)
```

#### `usingProvider(string $provider, string $model): self`

Set the AI provider and model.

```php
AiMeter::usingProvider('openai', 'gpt-4o-mini')
```

#### `feature(string $feature): self`

Set the feature name for categorization.

```php
AiMeter::feature('chat')
```

#### `billingMode(string $mode): self`

Set the billing mode (`'plan'` or `'credits'`).

```php
AiMeter::billingMode('credits')
```

#### `withMeta(array $meta): self`

Add metadata for tracking.

```php
AiMeter::withMeta(['ticket_id' => 123])
```

#### `withIdempotencyKey(string $key): self`

Set idempotency key to prevent duplicate records.

```php
AiMeter::withIdempotencyKey('unique-request-id-123')
```

#### `withManualUsage(array $usage): self`

Set manual usage data for providers that don't return usage.

```php
AiMeter::withManualUsage([
    'input_tokens' => 450,
    'output_tokens' => 900,
    'total_tokens' => 1350,
])
```

#### `call(callable $callback): MeteredResponse`

Execute AI call and record usage.

```php
$response = AiMeter::forUser($user)
    ->call(function () {
        return OpenAI::chat()->create([...]);
    });
```

## MeteredResponse

Response object returned by `AiMeter::call()`.

### Methods

#### `getResponse(): mixed`

Get the original provider response.

```php
$response = $meteredResponse->getResponse();
```

#### `getUsage(): ProviderUsage`

Get usage information.

```php
$usage = $meteredResponse->getUsage();
echo $usage->totalTokens;
echo $usage->totalCost;
```

#### `getLimitCheck(): LimitCheckResult`

Get limit check result.

```php
$limitCheck = $meteredResponse->getLimitCheck();
echo $limitCheck->usagePercentage;
```

#### `getRemainingTokens(): ?int`

Get remaining tokens in period (or null if unlimited).

```php
$remaining = $meteredResponse->getRemainingTokens();
```

#### `getRemainingCost(): ?float`

Get remaining cost in period (or null if unlimited).

```php
$remaining = $meteredResponse->getRemainingCost();
```

#### `isApproachingLimit(): bool`

Check if usage exceeds 80% of limit.

```php
if ($meteredResponse->isApproachingLimit()) {
    // Handle approaching limit
}
```

#### `isLimitReached(): bool`

Check if hard limit is reached.

```php
if ($meteredResponse->isLimitReached()) {
    // Handle limit reached
}
```

## ProviderUsage

Usage data from AI provider.

### Properties

- `inputTokens: ?int` - Input tokens used
- `outputTokens: ?int` - Output tokens used
- `totalTokens: ?int` - Total tokens used
- `inputCost: ?float` - Input cost
- `outputCost: ?float` - Output cost
- `totalCost: ?float` - Total cost
- `currency: ?string` - Currency code

### Methods

#### `toArray(): array`

Convert to array.

```php
$array = $usage->toArray();
```

#### `fromArray(array $data): self`

Create from array.

```php
$usage = ProviderUsage::fromArray($data);
```

## LimitCheckResult

Result of limit check.

### Properties

- `allowed: bool` - Is usage allowed?
- `approaching: bool` - Is usage approaching limit (>80%)?
- `hardLimitReached: bool` - Has hard limit been reached?
- `remainingTokens: ?int` - Remaining tokens
- `remainingCost: ?float` - Remaining cost
- `usagePercentage: float` - Usage percentage (0-100)

### Factory Methods

#### `allowed(int $remainingTokens, float $remainingCost, float $usagePercentage): self`

Create allowed result.

```php
$result = LimitCheckResult::allowed(
    remainingTokens: 1000,
    remainingCost: 50.0,
    usagePercentage: 50.0
);
```

#### `limitReached(): self`

Create limit reached result.

```php
$result = LimitCheckResult::limitReached();
```

#### `unlimited(): self`

Create unlimited result.

```php
$result = LimitCheckResult::unlimited();
```

## Models

### AiPlan

Plan model for usage limits.

#### Relationships

- `subscriptions: HasMany` - Subscriptions using this plan
- `previousPlan: BelongsTo` - Previous plan (for plan changes)

#### Methods

- `hasUnlimitedTokens(): bool` - Check if tokens are unlimited
- `hasUnlimitedCost(): bool` - Check if cost is unlimited
- `allowsOverage(): bool` - Check if overages are allowed

### AiSubscription

Subscription model linking billable entities to plans.

#### Relationships

- `plan: BelongsTo` - Plan
- `previousPlan: BelongsTo` - Previous plan
- `billable: MorphTo` - Billable entity (User, Tenant, etc.)

#### Methods

- `isActive(): bool` - Is subscription active?
- `isExpired(): bool` - Has subscription expired?
- `isInTrial(): bool` - Is subscription in trial?
- `isInGracePeriod(): bool` - Is subscription in grace period?

### AiUsage

Usage record model.

#### Scopes

- `forBillable($billable)` - Filter by billable entity
- `byProvider(string $provider)` - Filter by provider
- `byModel(string $model)` - Filter by model
- `byFeature(string $feature)` - Filter by feature
- `inPeriod(Carbon $start, Carbon $end)` - Filter by period

#### Relationships

- `billable: MorphTo` - Billable entity

### AiCreditWallet

Credit wallet model.

#### Relationships

- `billable: MorphTo` - Billable entity
- `transactions: HasMany` - Credit transactions

#### Methods

- `addCredits(float $amount, string $reason, array $meta = []): AiCreditTransaction` - Add credits
- `deductCredits(float $amount, string $reason, array $meta = []): AiCreditTransaction` - Deduct credits
- `hasSufficientBalance(float $amount): bool` - Check if balance is sufficient

### AiCreditTransaction

Credit transaction model.

#### Relationships

- `wallet: BelongsTo` - Credit wallet

### AiOverage

Overage charge model.

#### Relationships

- `billable: MorphTo` - Billable entity
- `usage: BelongsTo` - Usage record (if linked)

#### Methods

- `isSynced(): bool` - Check if synced to billing system
- `markAsSynced(string $syncId): void` - Mark as synced

### AiUsageLimitOverride

Limit override model.

#### Relationships

- `billable: MorphTo` - Billable entity

## Period

Period utility class.

### Methods

#### `fromConfig(array $config): self`

Create period from configuration.

```php
$period = Period::fromConfig(config('ai-metering.period'));
```

#### `getStart(): Carbon`

Get current period start.

```php
$start = $period->getStart();
```

#### `getEnd(): Carbon`

Get current period end (exclusive).

```php
$end = $period->getEnd();
```

#### `contains(Carbon $date): bool`

Check if date is in current period.

```php
if ($period->contains(Carbon::now())) {
    // Date is in period
}
```

#### `getNext(): self`

Get next period.

```php
$nextPeriod = $period->getNext();
```

#### `getPrevious(): self`

Get previous period.

```php
$previousPeriod = $period->getPrevious();
```

#### `getStartForDate(Carbon $date): Carbon`

Get period start for specific date.

```php
$start = $period->getStartForDate(Carbon::parse('2024-03-15'));
```

#### `getEndForDate(Carbon $date): Carbon`

Get period end for specific date.

```php
$end = $period->getEndForDate(Carbon::parse('2024-03-15'));
```

## Exceptions

### AiLimitExceededException

Thrown when usage limit is exceeded.

```php
use Ajooda\AiMetering\Exceptions\AiLimitExceededException;

try {
    $response = AiMeter::forUser($user)->call(...);
} catch (AiLimitExceededException $e) {
    // Handle limit exceeded
}
```

### AiCreditsInsufficientException

Thrown when credits are insufficient.

```php
use Ajooda\AiMetering\Exceptions\AiCreditsInsufficientException;

try {
    $response = AiMeter::forUser($user)->billingMode('credits')->call(...);
} catch (AiCreditsInsufficientException $e) {
    $balance = $e->getBalance();
    $required = $e->getRequired();
}
```

### AiProviderException

Thrown when provider call fails.

```php
use Ajooda\AiMetering\Exceptions\AiProviderException;

try {
    $response = AiMeter::forUser($user)->call(...);
} catch (AiProviderException $e) {
    // Handle provider error
}
```

### AiBillingException

Thrown when billing operation fails.

```php
use Ajooda\AiMetering\Exceptions\AiBillingException;

try {
    // Billing operation
} catch (AiBillingException $e) {
    // Handle billing error
}
```

### AiPlanNotFoundException

Thrown when plan is not found.

```php
use Ajooda\AiMetering\Exceptions\AiPlanNotFoundException;

try {
    $plan = AiPlan::findOrFail($id);
} catch (AiPlanNotFoundException $e) {
    // Handle plan not found
}
```

## Events

All events are in the `Ajooda\AiMetering\Events` namespace.

### AiUsageRecorded

Fired when usage is recorded.

**Properties**:
- `usage: AiUsage` - Usage record
- `billable: mixed` - Billable entity
- `providerUsage: ProviderUsage` - Provider usage data

### AiLimitApproaching

Fired when usage exceeds 80% of limit.

**Properties**:
- `billable: mixed` - Billable entity
- `usagePercentage: float` - Usage percentage (0-100)
- `remainingTokens: ?int` - Remaining tokens
- `remainingCost: ?float` - Remaining cost

### AiLimitReached

Fired when hard limit is reached.

**Properties**:
- `billable: mixed` - Billable entity
- `limitType: string` - Limit type ('tokens' or 'cost')

### AiProviderCallFailed

Fired when provider call fails.

**Properties**:
- `exception: \Exception` - Exception that occurred
- `provider: string` - Provider name
- `model: string` - Model name

### AiOverageCharged

Fired when overage is charged.

**Properties**:
- `overage: AiOverage` - Overage record
- `amount: float` - Overage amount
- `currency: string` - Currency code

### AiPlanChanged

Fired when plan changes.

**Properties**:
- `subscription: AiSubscription` - Subscription
- `oldPlan: ?AiPlan` - Previous plan
- `newPlan: AiPlan` - New plan

### AiCreditsAdded

Fired when credits are added.

**Properties**:
- `wallet: AiCreditWallet` - Credit wallet
- `amount: float` - Amount added
- `transaction: AiCreditTransaction` - Transaction record

### AiCreditsDeducted

Fired when credits are deducted.

**Properties**:
- `wallet: AiCreditWallet` - Credit wallet
- `amount: float` - Amount deducted
- `transaction: AiCreditTransaction` - Transaction record

### AiSubscriptionExpired

Fired when subscription expires.

**Properties**:
- `subscription: AiSubscription` - Expired subscription

## Artisan Commands

### ai-metering:report

Generate usage report.

```bash
php artisan ai-metering:report
php artisan ai-metering:report --month=2024-01
php artisan ai-metering:report --billable-type="App\Models\User" --billable=1
```

### ai-metering:cleanup

Cleanup old usage records.

```bash
php artisan ai-metering:cleanup
php artisan ai-metering:cleanup --days=90
php artisan ai-metering:cleanup --dry-run
```

### ai-metering:sync-stripe-overages

Sync overages to Stripe.

```bash
php artisan ai-metering:sync-stripe-overages
php artisan ai-metering:sync-stripe-overages --limit=50
```

### ai-metering:validate

Validate configuration and data integrity.

```bash
php artisan ai-metering:validate
```

### ai-metering:migrate-plan

Migrate billable entity to new plan.

```bash
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan"
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan" --from-plan="basic-plan"
```

### ai-metering:sync-plans

List all active plans.

```bash
php artisan ai-metering:sync-plans
```

## Middleware

### ai.quota

Enforce quota limits on routes.

```php
Route::middleware(['auth', 'ai.quota'])->group(function () {
    Route::post('/ai/chat', [ChatController::class, 'chat']);
});
```

**Response Headers**:
- `X-Remaining-Tokens`: Remaining tokens in period
- `X-Remaining-Cost`: Remaining cost in period
- `X-Usage-Percentage`: Usage percentage (0-100)

**Response Codes**:
- `200`: Usage allowed
- `429`: Limit exceeded

## Next Steps

- [Usage Guide](usage.md) - Usage examples
- [Configuration](configuration.md) - Configuration options
- [Features](features/) - Feature documentation

