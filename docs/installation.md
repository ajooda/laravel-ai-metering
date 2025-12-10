# Installation

This guide will walk you through installing and setting up Laravel AI Metering in your Laravel application.

## Requirements

- PHP >= 8.1 (PHP >= 8.2 for Laravel 11.x and 12.x)
- Laravel 10.x, 11.x, or 12.x
- Database (MySQL, PostgreSQL, SQLite, or SQL Server)

### Version Compatibility

| Laravel Version | PHP Version | Package Version | Status |
|----------------|-------------|----------------|--------|
| 10.x           | >= 8.1      | ^1.0           | ✅ Supported |
| 11.x           | >= 8.2      | ^1.0           | ✅ Supported |
| 12.x           | >= 8.2      | ^1.0           | ✅ Supported |

## Step 1: Install via Composer

```bash
composer require ajooda/laravel-ai-metering
```

## Step 2: Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-metering-config
```

This creates `config/ai-metering.php` with default settings. You can customize it later.

## Step 3: Publish and Run Migrations

Publish the migrations:

```bash
php artisan vendor:publish --tag=ai-metering-migrations
```

Run the migrations:

```bash
php artisan migrate
```

> **Note**: Make sure your database connection is configured in `.env` before running migrations. The package uses your default database connection unless specified otherwise via `AI_METERING_DB_CONNECTION`.

## Step 4: Verify Installation

Verify the package is installed correctly:

```bash
php artisan ai-metering:validate
```

This command checks:
- Configuration validity
- Database connectivity
- Data integrity
- Orphaned records

## Optional: Install AI Provider SDKs

The package works with any AI provider SDK. Install the ones you need:

### OpenAI

```bash
composer require openai-php/laravel
```

### Anthropic

```bash
composer require anthropic-php/sdk
```

### Stripe Billing (Optional)

If you want to use Stripe billing integration:

```bash
composer require laravel/cashier
```

## Environment Variables

The package supports various environment variables for configuration. Most have sensible defaults, so you can start using the package immediately.

### Most Common Variables

Add these to your `.env` file if you need to customize behavior:

```env
# Default Provider
AI_METERING_DEFAULT_PROVIDER=openai

# Billing Configuration
AI_METERING_BILLING_DRIVER=Ajooda\AiMetering\Services\Billing\NullBillingDriver
AI_METERING_OVERAGE_BEHAVIOR=block
AI_METERING_CURRENCY=usd

# Period Configuration
AI_METERING_PERIOD_TYPE=monthly
AI_METERING_TIMEZONE=UTC
```

For a complete list of environment variables, see [Configuration](configuration.md).

## Quick Test

After installation, you can test the package with a simple example:

```php
use Ajooda\AiMetering\Facades\AiMeter;
use OpenAI\Laravel\Facades\OpenAI;

$response = AiMeter::forUser(auth()->user())
    ->billable(auth()->user())
    ->usingProvider('openai', 'gpt-4o-mini')
    ->feature('test')
    ->call(function () {
        return OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ]);
    });

echo "Tokens used: " . $response->getUsage()->totalTokens;
```

## Next Steps

- [Configuration](configuration.md) - Configure providers, billing, and periods
- [Usage Guide](usage.md) - Learn how to meter AI calls
- [Plans & Quotas](features/plans-and-quotas.md) - Set up usage limits

