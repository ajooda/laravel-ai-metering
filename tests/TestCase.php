<?php

namespace Ajooda\AiMetering\Tests;

use Ajooda\AiMetering\AiMeteringServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            AiMeteringServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('ai-metering.billing.driver', \Ajooda\AiMetering\Services\Billing\NullBillingDriver::class);
        $app['config']->set('ai-metering.performance.cache_limit_checks', false);
        $app['config']->set('ai-metering.performance.queue_usage_recording', false);
    }
}
