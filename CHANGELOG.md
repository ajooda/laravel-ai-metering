# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-10

### Added

- Initial release of Laravel AI Metering package

#### Core Features

- Fluent API for metering AI usage with `AiMeter` facade
- Automatic token and cost tracking from AI provider responses
- Support for OpenAI, Anthropic, and custom providers
- Manual usage tracking for providers without automatic usage reporting
- Idempotency support to prevent duplicate usage records
- Metadata tracking for enhanced analytics and filtering

#### Quota Management

- Configurable limits (tokens, cost) per plan
- Per-tenant and per-user limit overrides via `AiUsageLimitOverride`
- Soft and hard limit enforcement
- Period-based quota tracking (monthly, weekly, daily, yearly, rolling)
- Calendar and rolling period alignment
- Timezone-aware period calculations via `Period` class
- Limit check caching for improved performance

#### Billing Integration

- Plan-based billing (subscription mode)
- Credit-based billing (wallet mode)
- Stripe/Cashier integration via `CashierBillingDriver`
- Automatic Stripe ID syncing via `AutoSyncStripeIdsObserver`
- Overage handling (block, charge, allow)
- Batch and immediate overage sync strategies
- Credit wallet with transaction history
- Pessimistic locking for credit transactions
- Payment failure grace period support
- Currency conversion support

#### Webhook Handling

- Automatic webhook handling for Stripe/Cashier events
- `HandleCashierWebhooks` listener for subscription lifecycle events
- Support for subscription cancellations, updates, and payment failures

#### Models

- `AiUsage` - Usage records with comprehensive tracking (user, tenant, provider, model, feature, metadata)
- `AiPlan` - Subscription plans with limits and overage pricing
- `AiSubscription` - Active subscriptions with lifecycle management (trial, grace period, expiration)
- `AiCreditWallet` - Credit wallets for prepaid billing
- `AiCreditTransaction` - Credit transaction history with audit trail
- `AiUsageLimitOverride` - Period-specific limit overrides
- `AiOverage` - Overage tracking for Stripe sync

#### Services

- `UsageRecorder` - Records usage with idempotency support and batch recording
- `UsageLimiter` - Checks limits with caching and period awareness
- `CostCalculator` - Calculates costs from tokens based on provider pricing
- `PlanResolver` - Resolves active plans and subscriptions for billable entities
- `ProviderFactory` - Creates provider instances with cost calculation
- `BillingDriver` - Extensible billing driver interface

#### Providers

- `OpenAiProvider` - OpenAI API integration with automatic usage extraction
- `AnthropicProvider` - Anthropic API integration with automatic usage extraction
- `ManualProvider` - Manual usage reporting for custom providers

#### Billing Drivers

- `NullBillingDriver` - No-op driver for internal tracking only
- `CashierBillingDriver` - Stripe/Cashier integration with overage syncing

#### Middleware

- `EnforceAiQuota` - Route-level quota enforcement with response headers

#### Events

- `AiUsageRecorded` - Dispatched when usage is recorded
- `AiLimitApproaching` - Dispatched when usage exceeds 80% of limit
- `AiLimitReached` - Dispatched when hard limit is reached
- `AiProviderCallFailed` - Dispatched when provider call fails
- `AiOverageCharged` - Dispatched when overage is charged
- `AiPlanChanged` - Dispatched when plan changes
- `AiCreditsAdded` - Dispatched when credits are added
- `AiCreditsDeducted` - Dispatched when credits are deducted
- `AiSubscriptionExpired` - Dispatched when subscription expires

#### Artisan Commands

- `ai-metering:report` - Generate usage reports with filtering options
- `ai-metering:cleanup` - Prune old usage records based on retention policy
- `ai-metering:sync-stripe-overages` - Sync overages to Stripe (batch mode)
- `ai-metering:sync-plans` - List and manage active plans
- `ai-metering:validate` - Validate configuration and data integrity
- `ai-metering:migrate-plan` - Migrate billable entities between plans

#### Multi-Tenancy

- Provider-agnostic tenant resolution
- `TenantResolver` interface for custom implementations
- `NullTenantResolver` for single-tenant apps
- Support for any tenancy package (Stancl/Tenancy, Spatie Multitenancy, etc.)

#### Support Classes

- `MeteredResponse` - Type-safe response wrapper with usage and limit information
- `ProviderUsage` - Usage data structure (tokens, costs, currency)
- `LimitCheckResult` - Limit check result with remaining quotas and percentages
- `Period` - Period calculation utility with timezone support
- `ConditionalSoftDeletes` - Trait for optional soft deletes

#### Exceptions

- `AiLimitExceededException` - Thrown when usage limit is exceeded
- `AiCreditsInsufficientException` - Thrown when credits are insufficient
- `AiProviderException` - Thrown when provider call fails
- `AiBillingException` - Thrown when billing operation fails
- `AiPlanNotFoundException` - Thrown when plan is not found

#### Contracts

- `ProviderClient` - Interface for AI provider implementations
- `TenantResolver` - Interface for tenant resolution
- `BillingDriver` - Interface for billing driver implementations

#### Performance

- Caching for limit checks and plan lookups (configurable TTL)
- Queue support for async usage recording
- Batch recording support for high-volume scenarios
- Optimized database queries with indexes
- Pessimistic locking for concurrent operations

#### Developer Experience

- Comprehensive Eloquent scopes for querying usage (`forBillable`, `byProvider`, `byModel`, `byFeature`, `inPeriod`)
- Fluent API for metering with method chaining
- Type-safe response objects
- Custom exceptions for granular error handling
- Extensive test coverage

#### Configuration

- Comprehensive configuration file with sensible defaults
- Environment variable support for all settings
- Configurable providers, billing drivers, periods, and performance settings
- Security and logging configuration options

#### Documentation

- Comprehensive README with quick start guide
- Complete documentation in `docs/` directory:
  - Overview and installation guides
  - Configuration reference
  - Usage guide with examples
  - Feature-specific documentation (plans, credits, billing, multi-tenancy, periods)
  - Advanced topics and extensibility guide
  - Complete API reference
  - Troubleshooting guide
- Contributing guidelines
- Code of Conduct
- Security policy

#### Requirements

- PHP >= 8.1 (PHP >= 8.2 for Laravel 11.x and 12.x)
- Laravel 10.x, 11.x, or 12.x
- Database support (MySQL, PostgreSQL, SQLite, SQL Server)

### Security

- Configurable security settings (validation, sanitization, race condition prevention)
- GDPR-compliant data deletion methods (via Eloquent delete methods)
- Configurable logging with failure tracking
- Race condition prevention for credit transactions (via cache locks)
- Pessimistic locking for concurrent operations (via `lockForUpdate()`)
- Configurable security options for feature name validation and metadata sanitization

[1.0.0]: https://github.com/ajooda/laravel-ai-metering/releases/tag/v1.0.0
