# Laravel AI Metering

[![Latest Version](https://img.shields.io/packagist/v/ajooda/laravel-ai-metering.svg?style=flat-square)](https://packagist.org/packages/ajooda/laravel-ai-metering)
[![Total Downloads](https://img.shields.io/packagist/dt/ajooda/laravel-ai-metering.svg?style=flat-square)](https://packagist.org/packages/ajooda/laravel-ai-metering)
[![License](https://img.shields.io/packagist/l/ajooda/laravel-ai-metering.svg?style=flat-square)](https://packagist.org/packages/ajooda/laravel-ai-metering)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-777BB4.svg?style=flat-square)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-FF2D20.svg?style=flat-square)](https://laravel.com)
[![Stability](https://img.shields.io/badge/stability-stable-green.svg?style=flat-square)](https://packagist.org/packages/ajooda/laravel-ai-metering)
[![Documentation](https://img.shields.io/badge/docs-available-brightgreen.svg?style=flat-square)](docs/)

**Usage metering, quotas, and billing for AI applications.**

A production-ready Laravel package that automatically tracks AI usage, enforces quotas, and integrates with Stripe for billing. Perfect for SaaS applications that need to meter and bill AI usage.

## Table of Contents

- [Features](#features)
- [Why Choose This Package?](#why-choose-this-package)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
  - [Installation](#installation)
  - [Usage Example](#usage-example)
- [Documentation](#documentation)
- [Quick Links](#quick-links)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## Features

- 🎯 **Simple Developer Experience** - Fluent API for metering AI usage
- 📊 **Usage Tracking** - Automatic token and cost tracking
- 🚦 **Quota Management** - Configurable limits (tokens, cost, per-plan, per-tenant)
- 💳 **Billing Integration** - Stripe/Cashier support with credit-based and subscription modes
- 🏢 **Multi-Tenancy** - Works with or without tenancy packages
- 🔌 **Provider Agnostic** - Support for OpenAI, Anthropic, and custom providers
- ⚡ **Performance** - Caching, queue support, and optimized queries

## Why Choose This Package?

- **Zero Configuration Required** - Start tracking usage immediately, no plans or subscriptions needed
- **Production Ready** - Battle-tested with comprehensive test coverage and security features
- **Automatic Processing** - Automatic usage extraction, cost calculation, and billing integration
- **Flexible Billing** - Support for both subscription-based and credit-based billing models
- **Multi-Tenant Ready** - Built-in support for multi-tenant applications without coupling to specific packages
- **Extensible** - Easy to add custom providers, billing drivers, and tenant resolvers
- **Well Documented** - Comprehensive documentation with examples and troubleshooting guides

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

### Optional Dependencies

- **OpenAI**: `openai-php/laravel` (for OpenAI provider)
- **Anthropic**: `anthropic-php/sdk` (for Anthropic provider)
- **Stripe/Cashier**: `laravel/cashier` (for Stripe billing integration)

## Quick Start

> **Note**: This package supports Laravel Package Auto-Discovery, so you do not need to manually register the service provider.

### Installation

```bash
# 1. Install the package
composer require ajooda/laravel-ai-metering

# 2. Publish and run migrations
php artisan vendor:publish --tag=ai-metering-migrations
php artisan migrate

# 3. Verify installation
php artisan ai-metering:validate
```

### Usage Example

**Full Example** - Complete usage with all options:

```php
use Ajooda\AiMetering\Facades\AiMeter;
use OpenAI\Laravel\Facades\OpenAI;

$response = AiMeter::forUser(auth()->user())
    ->billable(auth()->user())
    ->usingProvider('openai', 'gpt-4o-mini')
    ->feature('chat')
    ->call(function () {
        return OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ]);
    });

// Access the AI response
$aiResponse = $response->getResponse();

// Check usage
$usage = $response->getUsage();
echo "Tokens: {$usage->totalTokens}, Cost: \${$usage->totalCost}";
```

**Minimal Example** - Just track usage:

```php
use Ajooda\AiMetering\Facades\AiMeter;

$response = AiMeter::forUser(auth()->user())
    ->billable(auth()->user())
    ->usingProvider('openai', 'gpt-4o-mini')
    ->call(fn() => OpenAI::chat()->create([...]));

$usage = $response->getUsage();
```

**That's it!** Usage is automatically tracked. No plans or subscriptions required for basic tracking.

## Documentation

Comprehensive documentation is available in the [`docs/`](docs/) directory:

- **[Overview](docs/overview.md)** - What this package does and when to use it
- **[Installation](docs/installation.md)** - Detailed installation and setup guide
- **[Configuration](docs/configuration.md)** - All configuration options explained
- **[Usage Guide](docs/usage.md)** - Common usage flows and examples
- **[Features](docs/features/)** - Detailed feature documentation
  - [Plans & Quotas](docs/features/plans-and-quotas.md) - Setting up usage limits
  - [Credits](docs/features/credits.md) - Credit-based billing
  - [Billing Integration](docs/features/billing.md) - Stripe/Cashier integration
  - [Multi-Tenancy](docs/features/multi-tenancy.md) - Multi-tenant usage tracking
  - [Periods](docs/features/periods.md) - Period configuration
- **[Advanced Topics](docs/advanced.md)** - Extensibility, customization, events, hooks
- **[API Reference](docs/api-reference.md)** - Complete API documentation
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions

## Quick Links

### Common Tasks

- [Setting up plans and quotas](docs/features/plans-and-quotas.md)
- [Credit-based billing](docs/features/credits.md)
- [Stripe integration](docs/features/billing.md)
- [Multi-tenant setup](docs/features/multi-tenancy.md)
- [Using middleware](docs/usage.md#using-middleware)
- [Querying usage](docs/usage.md#querying-usage)

### Artisan Commands

```bash
# Generate usage report
php artisan ai-metering:report

# Cleanup old usage records
php artisan ai-metering:cleanup

# Sync Stripe overages
php artisan ai-metering:sync-stripe-overages

# Validate configuration
php artisan ai-metering:validate

# Migrate plan
php artisan ai-metering:migrate-plan "App\Models\User" 1 "pro-plan"

# List plans
php artisan ai-metering:sync-plans
```

See [API Reference](docs/api-reference.md#artisan-commands) for complete command documentation.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Quick Start for Contributors

1. **Fork and clone the repository**:
   ```bash
   git clone https://github.com/ajooda/laravel-ai-metering.git
   cd laravel-ai-metering
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Run tests**:
   ```bash
   composer test
   ```

4. **Run tests with coverage**:
   ```bash
   composer test-coverage
   ```

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Support

### Getting Help

- 🐛 **Bug Reports**: [Open an issue](https://github.com/ajooda/laravel-ai-metering/issues)
- 💬 **Questions**: [GitHub Discussions](https://github.com/ajooda/laravel-ai-metering/discussions)
- 📖 **Documentation**: [Full Documentation](docs/)
- 🔒 **Security Issues**: Email **abdalhadijouda@gmail.com** or see [SECURITY.md](SECURITY.md)

### Maintainers

**Primary Maintainer**: AbdAlhadi Jouda (abdalhadijouda@gmail.com)

**Response Times**:
- Security vulnerabilities: 48 hours initial response
- Bug reports: 3-5 business days
- Questions: 5-7 business days
- Feature requests: As time permits
