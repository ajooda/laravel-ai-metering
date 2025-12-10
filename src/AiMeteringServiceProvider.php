<?php

namespace Ajooda\AiMetering;

use Ajooda\AiMetering\Contracts\TenantResolver;
use Ajooda\AiMetering\Resolvers\NullTenantResolver;
use Ajooda\AiMetering\Services\AiMeter;
use Ajooda\AiMetering\Services\Billing\BillingDriver;
use Ajooda\AiMetering\Services\CostCalculator;
use Ajooda\AiMetering\Services\PlanResolver;
use Ajooda\AiMetering\Services\ProviderFactory;
use Ajooda\AiMetering\Services\UsageLimiter;
use Ajooda\AiMetering\Services\UsageRecorder;
use Illuminate\Support\ServiceProvider;

class AiMeteringServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-metering.php',
            'ai-metering'
        );

        $this->app->singleton(
            TenantResolver::class,
            config('ai-metering.tenant_resolver', NullTenantResolver::class)
        );

        $this->app->singleton(CostCalculator::class);
        $this->app->singleton(PlanResolver::class);
        $this->app->singleton(UsageRecorder::class);
        $this->app->singleton(ProviderFactory::class, function ($app) {
            return new ProviderFactory($app->make(CostCalculator::class));
        });
        $this->app->singleton(UsageLimiter::class, function ($app) {
            return new UsageLimiter($app->make(PlanResolver::class));
        });

        $this->app->singleton(
            BillingDriver::class,
            config('ai-metering.billing.driver', \Ajooda\AiMetering\Services\Billing\NullBillingDriver::class)
        );

        $this->app->singleton(AiMeter::class, function ($app) {
            return new AiMeter(
                $app->make(UsageRecorder::class),
                $app->make(UsageLimiter::class),
                $app->make(CostCalculator::class),
                $app->make(PlanResolver::class),
                $app->make(BillingDriver::class)
            );
        });

        $this->app->alias(AiMeter::class, 'ai-meter');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/ai-metering.php' => config_path('ai-metering.php'),
        ], 'ai-metering-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'ai-metering-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views/ai-metering', 'ai-metering');

        $this->app['router']->aliasMiddleware('ai.quota', \Ajooda\AiMetering\Http\Middleware\EnforceAiQuota::class);

        if (class_exists(\Laravel\Cashier\Events\WebhookReceived::class)) {
            $this->app['events']->listen(
                \Laravel\Cashier\Events\WebhookReceived::class,
                \Ajooda\AiMetering\Listeners\HandleCashierWebhooks::class
            );
        }

        if (class_exists(\Laravel\Cashier\Subscription::class)
            && config('ai-metering.auto_sync_stripe_ids', true)) {
            \Ajooda\AiMetering\Models\AiSubscription::observe(
                \Ajooda\AiMetering\Observers\AutoSyncStripeIdsObserver::class
            );
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Ajooda\AiMetering\Console\Commands\ReportUsageCommand::class,
                \Ajooda\AiMetering\Console\Commands\SyncPlansCommand::class,
                \Ajooda\AiMetering\Console\Commands\CleanupOldUsageCommand::class,
                \Ajooda\AiMetering\Console\Commands\SyncStripeOveragesCommand::class,
                \Ajooda\AiMetering\Console\Commands\ValidateCommand::class,
                \Ajooda\AiMetering\Console\Commands\MigratePlanCommand::class,
            ]);
        }
    }
}
