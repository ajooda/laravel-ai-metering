# Configuration

This guide covers all configuration options available in Laravel AI Metering.

## Configuration File

The configuration file is located at `config/ai-metering.php`. You can customize it after publishing:

```bash
php artisan vendor:publish --tag=ai-metering-config
```

## Providers

Configure AI providers and their pricing models.

### Default Provider

```php
'default_provider' => env('AI_METERING_DEFAULT_PROVIDER', 'openai'),
```

### Provider Configuration

Each provider must have a `class` that implements `ProviderClient`. Models within each provider define pricing:

```php
'providers' => [
    'openai' => [
        'class' => \Ajooda\AiMetering\Services\Providers\OpenAiProvider::class,
        'models' => [
            'gpt-4o-mini' => [
                'input_price_per_1k' => 0.00015,  // Price per 1k input tokens
                'output_price_per_1k' => 0.00060,  // Price per 1k output tokens
            ],
            'gpt-4o' => [
                'input_price_per_1k' => 0.0025,
                'output_price_per_1k' => 0.010,
            ],
        ],
    ],
    'anthropic' => [
        'class' => \Ajooda\AiMetering\Services\Providers\AnthropicProvider::class,
        'models' => [
            'claude-3-5-sonnet-20241022' => [
                'input_price_per_1k' => 0.003,
                'output_price_per_1k' => 0.015,
            ],
        ],
    ],
    'manual' => [
        'class' => \Ajooda\AiMetering\Services\Providers\ManualProvider::class,
    ],
],
```

### Adding Custom Providers

See [Extending Providers](advanced.md#extending-providers) for details on creating custom providers.

## Billing Configuration

### Billing Driver

```php
'billing' => [
    'driver' => env('AI_METERING_BILLING_DRIVER', \Ajooda\AiMetering\Services\Billing\NullBillingDriver::class),
],
```

Available drivers:
- `NullBillingDriver`: No billing (default)
- `CashierBillingDriver`: Stripe/Cashier integration

### Overage Behavior

What happens when usage exceeds plan limits:

```php
'overage_behavior' => env('AI_METERING_OVERAGE_BEHAVIOR', 'block'), // 'block', 'charge', 'allow'
```

- `block`: Block usage when limit is exceeded (throws `AiLimitExceededException`)
- `charge`: Allow usage and charge for overages
- `allow`: Allow usage without charging (track only)

### Overage Sync Strategy

How overage charges are synced to Stripe:

```php
'overage_sync_strategy' => env('AI_METERING_OVERAGE_SYNC_STRATEGY', 'batch'), // 'immediate', 'batch'
```

- `immediate`: Sync immediately (may impact performance)
- `batch`: Sync in batches via `ai-metering:sync-stripe-overages` command

### Credit Overdraft

```php
'credit_overdraft_allowed' => env('AI_METERING_CREDIT_OVERDRAFT', false),
```

Allow negative credit balances (overdraft).

### Currency

```php
'currency' => env('AI_METERING_CURRENCY', 'usd'),
```

Default currency for billing and costs.

### Currency Conversion Rates

Define conversion rates between currencies:

```php
'currency_rates' => [
    'usd_eur' => 0.85,  // 1 USD = 0.85 EUR
    'eur_usd' => 1.18,  // 1 EUR = 1.18 USD
],
```

Update these rates regularly or integrate with a currency API.

### Payment Failure Grace Period

```php
'payment_failure_grace_period_days' => env('AI_METERING_PAYMENT_FAILURE_GRACE_PERIOD', 7),
```

Days of grace period after payment failure before blocking usage.

## Tenant Resolver

Configure how tenants are resolved (for multi-tenancy):

```php
'tenant_resolver' => env('AI_METERING_TENANT_RESOLVER', \Ajooda\AiMetering\Resolvers\NullTenantResolver::class),
```

See [Multi-Tenancy](features/multi-tenancy.md) for details.

## Period Configuration

Configure how usage periods are calculated:

```php
'period' => [
    'type' => env('AI_METERING_PERIOD_TYPE', 'monthly'), // 'monthly', 'weekly', 'daily', 'yearly', 'rolling'
    'alignment' => env('AI_METERING_PERIOD_ALIGNMENT', 'calendar'), // 'calendar' or 'rolling'
    'timezone' => env('AI_METERING_TIMEZONE', 'UTC'),
],
```

### Period Types

- `monthly`: Calendar month (1st to last day) or rolling 30 days
- `weekly`: Calendar week (Monday-Sunday) or rolling 7 days
- `daily`: Calendar day (00:00-23:59) or rolling 24 hours
- `yearly`: Calendar year or rolling 365 days
- `rolling`: Rolling period from subscription start

### Period Alignment

- `calendar`: Period aligns to calendar boundaries (e.g., 1st of month)
- `rolling`: Period starts from a specific date and rolls forward

See [Period Configuration](features/periods.md) for details.

## Storage Configuration

```php
'storage' => [
    'prune_after_days' => env('AI_METERING_PRUNE_AFTER_DAYS', 365),
    'connection' => env('AI_METERING_DB_CONNECTION', null), // null = default connection
],
```

- `prune_after_days`: Days after which old usage records are pruned (via `ai-metering:cleanup`)
- `connection`: Database connection name (null = default)

## Performance Configuration

```php
'performance' => [
    'cache_limit_checks' => env('AI_METERING_CACHE_LIMIT_CHECKS', true),
    'cache_ttl' => env('AI_METERING_CACHE_TTL', 300), // seconds
    'queue_usage_recording' => env('AI_METERING_QUEUE_RECORDING', false), // false or queue name
    'batch_size' => env('AI_METERING_BATCH_SIZE', 100),
],
```

- `cache_limit_checks`: Cache limit check results (recommended: true)
- `cache_ttl`: Cache TTL in seconds
- `queue_usage_recording`: Queue name for async usage recording (false = synchronous)
- `batch_size`: Batch size for bulk operations

> **Important**: If `queue_usage_recording` is enabled, ensure your queue worker is running.

## Security Configuration

```php
'security' => [
    'validate_feature_names' => env('AI_METERING_VALIDATE_FEATURES', true),
    'sanitize_metadata' => env('AI_METERING_SANITIZE_METADATA', true),
    'rate_limit_enabled' => env('AI_METERING_RATE_LIMIT', false),
    'prevent_race_conditions' => env('AI_METERING_PREVENT_RACE_CONDITIONS', true),
],
```

- `validate_feature_names`: Validate feature names (alphanumeric + underscore)
- `sanitize_metadata`: Sanitize metadata before storage
- `rate_limit_enabled`: Enable rate limiting (not implemented by default)
- `prevent_race_conditions`: Use database locks to prevent race conditions

## Logging Configuration

```php
'logging' => [
    'enabled' => env('AI_METERING_LOGGING_ENABLED', true),
    'level' => env('AI_METERING_LOG_LEVEL', 'info'), // 'debug', 'info', 'warning', 'error'
    'log_failures' => env('AI_METERING_LOG_FAILURES', true),
],
```

- `enabled`: Enable logging
- `level`: Log level
- `log_failures`: Log failed operations

## Feature Flags

```php
'features' => [
    'soft_deletes' => env('AI_METERING_SOFT_DELETES', false),
],
```

- `soft_deletes`: Enable soft deletes for models (not enabled by default)

## Auto Sync Stripe IDs

```php
'auto_sync_stripe_ids' => env('AI_METERING_AUTO_SYNC_STRIPE_IDS', true),
```

Automatically sync Stripe subscription and customer IDs to `AiSubscription.meta` when creating/updating subscriptions. This enables webhook handling to work automatically.

Set to `false` to disable and set Stripe IDs manually.

## Environment Variables Reference

All configuration options can be overridden via environment variables. See the table below:

| Variable | Default | Description |
|----------|---------|-------------|
| `AI_METERING_DEFAULT_PROVIDER` | `openai` | Default AI provider |
| `AI_METERING_BILLING_DRIVER` | `NullBillingDriver` | Billing driver class |
| `AI_METERING_OVERAGE_BEHAVIOR` | `block` | Overage behavior: `block`, `charge`, `allow` |
| `AI_METERING_OVERAGE_SYNC_STRATEGY` | `batch` | Overage sync strategy: `immediate`, `batch` |
| `AI_METERING_CREDIT_OVERDRAFT` | `false` | Allow credit overdraft |
| `AI_METERING_CURRENCY` | `usd` | Default currency |
| `AI_METERING_PAYMENT_FAILURE_GRACE_PERIOD` | `7` | Grace period days |
| `AI_METERING_TENANT_RESOLVER` | `NullTenantResolver` | Tenant resolver class |
| `AI_METERING_PERIOD_TYPE` | `monthly` | Period type |
| `AI_METERING_PERIOD_ALIGNMENT` | `calendar` | Period alignment |
| `AI_METERING_TIMEZONE` | `UTC` | Timezone for periods |
| `AI_METERING_PRUNE_AFTER_DAYS` | `365` | Days before pruning |
| `AI_METERING_DB_CONNECTION` | `null` | Database connection |
| `AI_METERING_CACHE_LIMIT_CHECKS` | `true` | Cache limit checks |
| `AI_METERING_CACHE_TTL` | `300` | Cache TTL (seconds) |
| `AI_METERING_QUEUE_RECORDING` | `false` | Queue name for async recording |
| `AI_METERING_BATCH_SIZE` | `100` | Batch size |
| `AI_METERING_VALIDATE_FEATURES` | `true` | Validate feature names |
| `AI_METERING_SANITIZE_METADATA` | `true` | Sanitize metadata |
| `AI_METERING_RATE_LIMIT` | `false` | Enable rate limiting |
| `AI_METERING_PREVENT_RACE_CONDITIONS` | `true` | Prevent race conditions |
| `AI_METERING_LOGGING_ENABLED` | `true` | Enable logging |
| `AI_METERING_LOG_LEVEL` | `info` | Log level |
| `AI_METERING_LOG_FAILURES` | `true` | Log failures |
| `AI_METERING_SOFT_DELETES` | `false` | Enable soft deletes |
| `AI_METERING_AUTO_SYNC_STRIPE_IDS` | `true` | Auto sync Stripe IDs |

## Next Steps

- [Usage Guide](usage.md) - Learn how to use the package
- [Features](features/) - Detailed feature documentation
- [Advanced Topics](advanced.md) - Advanced configuration and customization

