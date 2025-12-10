# Overview

Laravel AI Metering is a production-ready Laravel package for tracking AI usage, enforcing quotas, and integrating with billing systems. It provides a simple, fluent API for metering AI provider calls (OpenAI, Anthropic, or custom providers) and automatically tracks token usage, costs, and enforces limits.

## Requirements

- PHP >= 8.1 (PHP >= 8.2 for Laravel 11.x and 12.x)
- Laravel 10.x, 11.x, or 12.x
- Database (MySQL, PostgreSQL, SQLite, or SQL Server)

## What This Package Does

- **Usage Tracking**: Automatically tracks token usage and costs for AI provider calls
- **Quota Management**: Enforces configurable limits (tokens, cost) per plan, per tenant, or per user
- **Billing Integration**: Integrates with Stripe/Cashier for subscription and credit-based billing
- **Multi-Tenancy**: Works with or without tenancy packages
- **Provider Agnostic**: Supports OpenAI, Anthropic, and custom providers
- **Performance Optimized**: Includes caching, queue support, and optimized database queries

## When to Use This Package

Use this package when you need to:

- Track AI usage (tokens, costs) across your application
- Enforce usage limits based on subscription plans
- Bill users for AI usage (subscription-based or credit-based)
- Monitor and analyze AI usage patterns
- Integrate AI metering with existing billing systems (Stripe, etc.)
- Support multi-tenant applications with per-tenant usage tracking

## Key Concepts

### Metering

The package wraps your AI provider calls and automatically:
1. Executes the AI call
2. Extracts usage data (tokens, costs) from the response
3. Records usage in the database
4. Checks against configured limits
5. Handles billing (if configured)

### Plans & Subscriptions

- **Plans**: Define usage limits (token limits, cost limits) and features
- **Subscriptions**: Link billable entities (users, tenants) to plans
- **Periods**: Usage is tracked within configurable periods (monthly, weekly, daily, etc.)

### Billing Modes

- **Plan-based**: Usage is tracked against plan limits; overages can be charged
- **Credit-based**: Usage is deducted from a credit wallet balance

### Billable Entities

Any Eloquent model can be a billable entity (User, Tenant, Organization, etc.). Usage is tracked per billable entity.

## Architecture

The package is built around several core services:

- **AiMeter**: Main facade/service for metering AI calls
- **UsageRecorder**: Records usage data to the database
- **UsageLimiter**: Checks usage against limits
- **PlanResolver**: Resolves active plans for billable entities
- **CostCalculator**: Calculates costs based on provider pricing
- **BillingDriver**: Handles billing operations (Stripe, credits, etc.)

## Package Structure

```
src/
├── Services/          # Core services (AiMeter, UsageRecorder, etc.)
├── Models/           # Eloquent models (AiPlan, AiSubscription, AiUsage, etc.)
├── Events/           # Event classes for lifecycle hooks
├── Exceptions/       # Custom exceptions
├── Middleware/       # Quota enforcement middleware
├── Console/Commands/ # Artisan commands
└── Support/          # Helper classes (Period, ProviderUsage, etc.)
```

## Next Steps

- [Installation Guide](installation.md) - Get started with installation and setup
- [Configuration](configuration.md) - Configure providers, billing, and periods
- [Usage Guide](usage.md) - Learn how to meter AI calls
- [Features](features/) - Detailed feature documentation

