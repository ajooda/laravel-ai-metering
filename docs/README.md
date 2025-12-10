# Documentation

Welcome to the Laravel AI Metering documentation! This directory contains comprehensive documentation for the package.

## Getting Started

If you're new to the package, start here:

1. **[Overview](overview.md)** - Learn what this package does and when to use it
2. **[Installation](installation.md)** - Get the package installed and configured
3. **[Configuration](configuration.md)** - Configure providers, billing, and periods
4. **[Usage Guide](usage.md)** - Learn how to meter AI calls

## Core Documentation

- **[Overview](overview.md)** - High-level description and key concepts
- **[Installation](installation.md)** - Installation steps and requirements
- **[Configuration](configuration.md)** - All configuration options explained
- **[Usage Guide](usage.md)** - Common usage flows and examples

## Features

Detailed documentation for each major feature:

- **[Plans & Quotas](features/plans-and-quotas.md)** - Setting up usage limits and subscriptions
- **[Credits](features/credits.md)** - Credit-based billing and wallet management
- **[Billing Integration](features/billing.md)** - Stripe/Cashier integration and webhooks
- **[Multi-Tenancy](features/multi-tenancy.md)** - Multi-tenant usage tracking
- **[Periods](features/periods.md)** - Period configuration and calculation

## Advanced Topics

- **[Advanced Topics](advanced.md)** - Extensibility, customization, events, hooks, and performance
- **[API Reference](api-reference.md)** - Complete API documentation with all classes, methods, and properties
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions

## Quick Reference

### Most Common Tasks

- [Basic Usage Example](usage.md#basic-usage)
- [Setting up Plans](features/plans-and-quotas.md#creating-plans)
- [Credit-Based Billing](features/credits.md#using-credits-mode)
- [Stripe Integration](features/billing.md#stripecashier-integration)
- [Multi-Tenant Setup](features/multi-tenancy.md#using-with-tenants)
- [Querying Usage](usage.md#querying-usage)

### Artisan Commands

- [Usage Report](api-reference.md#ai-meteringreport)
- [Cleanup Old Usage](api-reference.md#ai-meteringcleanup)
- [Sync Stripe Overages](api-reference.md#ai-meteringsync-stripe-overages)
- [Validate Configuration](api-reference.md#ai-meteringvalidate)
- [Migrate Plan](api-reference.md#ai-meteringmigrate-plan)

### Events

- [AiUsageRecorded](advanced.md#events) - When usage is recorded
- [AiLimitApproaching](advanced.md#events) - When usage exceeds 80% of limit
- [AiLimitReached](advanced.md#events) - When hard limit is reached
- [AiOverageCharged](advanced.md#events) - When overage is charged
- [Full Event List](api-reference.md#events)

## Need Help?

- **Troubleshooting**: See [Troubleshooting Guide](troubleshooting.md)
- **Questions**: [GitHub Discussions](https://github.com/ajooda/laravel-ai-metering/discussions)
- **Bug Reports**: [GitHub Issues](https://github.com/ajooda/laravel-ai-metering/issues)
- **Security Issues**: Email abdalhadijouda@gmail.com or see [SECURITY.md](../SECURITY.md)

