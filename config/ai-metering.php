<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default AI provider to use when none is specified.
    |
    */

    'default_provider' => env('AI_METERING_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure available AI providers and their models with pricing.
    |
    */

    'providers' => [
        'openai' => [
            'class' => \Ajooda\AiMetering\Services\Providers\OpenAiProvider::class,
            'models' => [
                'gpt-4o-mini' => [
                    'input_price_per_1k' => 0.00015,
                    'output_price_per_1k' => 0.00060,
                ],
                'gpt-4o' => [
                    'input_price_per_1k' => 0.0025,
                    'output_price_per_1k' => 0.010,
                ],
                'gpt-4-turbo' => [
                    'input_price_per_1k' => 0.01,
                    'output_price_per_1k' => 0.03,
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
                'claude-3-opus-20240229' => [
                    'input_price_per_1k' => 0.015,
                    'output_price_per_1k' => 0.075,
                ],
            ],
        ],
        'manual' => [
            'class' => \Ajooda\AiMetering\Services\Providers\ManualProvider::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure billing driver and behavior.
    |
    */

    'billing' => [
        'driver' => env('AI_METERING_BILLING_DRIVER', \Ajooda\AiMetering\Services\Billing\NullBillingDriver::class),
        'overage_behavior' => env('AI_METERING_OVERAGE_BEHAVIOR', 'block'), // 'block', 'charge', 'allow'
        'overage_sync_strategy' => env('AI_METERING_OVERAGE_SYNC_STRATEGY', 'batch'), // 'immediate', 'batch'
        'credit_overdraft_allowed' => env('AI_METERING_CREDIT_OVERDRAFT', false),
        'currency' => env('AI_METERING_CURRENCY', 'usd'),
        'payment_failure_grace_period_days' => env('AI_METERING_PAYMENT_FAILURE_GRACE_PERIOD', 7),

        /*
        |--------------------------------------------------------------------------
        | Currency Conversion Rates
        |--------------------------------------------------------------------------
        |
        | Define conversion rates between currencies. Rates should be from
        | the key currency to the value. For example:
        | 'usd_eur' => 0.85 means 1 USD = 0.85 EUR
        | 'eur_usd' => 1.18 means 1 EUR = 1.18 USD
        |
        | You can also use USD as a base currency and convert through it.
        | Update these rates regularly or integrate with a currency API.
        |
        */
        'currency_rates' => [
            // Example rates (update with real-time rates or use external service)
            // 'usd_eur' => 0.85,
            // 'eur_usd' => 1.18,
            // 'usd_gbp' => 0.73,
            // 'gbp_usd' => 1.37,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolver
    |--------------------------------------------------------------------------
    |
    | Class that resolves the current tenant. Implement TenantResolver interface.
    |
    */

    'tenant_resolver' => env('AI_METERING_TENANT_RESOLVER', \Ajooda\AiMetering\Resolvers\NullTenantResolver::class),

    /*
    |--------------------------------------------------------------------------
    | Period Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how usage periods are calculated.
    |
    */

    'period' => [
        'type' => env('AI_METERING_PERIOD_TYPE', 'monthly'), // 'monthly', 'weekly', 'daily', 'yearly', 'rolling'
        'alignment' => env('AI_METERING_PERIOD_ALIGNMENT', 'calendar'), // 'calendar' or 'rolling'
        'timezone' => env('AI_METERING_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure data storage and retention.
    |
    */

    'storage' => [
        'prune_after_days' => env('AI_METERING_PRUNE_AFTER_DAYS', 365),
        'connection' => env('AI_METERING_DB_CONNECTION', null), // null = default connection
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching and queue settings for performance.
    |
    */

    'performance' => [
        'cache_limit_checks' => env('AI_METERING_CACHE_LIMIT_CHECKS', true),
        'cache_ttl' => env('AI_METERING_CACHE_TTL', 300), // seconds
        'queue_usage_recording' => env('AI_METERING_QUEUE_RECORDING', false), // false or queue name
        'batch_size' => env('AI_METERING_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security and validation settings.
    |
    */

    'security' => [
        'validate_feature_names' => env('AI_METERING_VALIDATE_FEATURES', true),
        'sanitize_metadata' => env('AI_METERING_SANITIZE_METADATA', true),
        'rate_limit_enabled' => env('AI_METERING_RATE_LIMIT', false),
        'prevent_race_conditions' => env('AI_METERING_PREVENT_RACE_CONDITIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior.
    |
    */

    'logging' => [
        'enabled' => env('AI_METERING_LOGGING_ENABLED', true),
        'level' => env('AI_METERING_LOG_LEVEL', 'info'), // 'debug', 'info', 'warning', 'error'
        'log_failures' => env('AI_METERING_LOG_FAILURES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable/disable optional features.
    |
    */

    'features' => [
        'soft_deletes' => env('AI_METERING_SOFT_DELETES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Sync Stripe IDs
    |--------------------------------------------------------------------------
    |
    | Automatically sync Stripe subscription and customer IDs to AiSubscription
    | meta field when creating/updating subscriptions. This enables webhook
    | handling to work automatically.
    |
    | Set to false to disable automatic syncing and set Stripe IDs manually.
    |
    */

    'auto_sync_stripe_ids' => env('AI_METERING_AUTO_SYNC_STRIPE_IDS', true),
];
