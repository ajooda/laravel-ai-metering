<?php

namespace Ajooda\AiMetering\Services;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Events\AiLimitApproaching;
use Ajooda\AiMetering\Events\AiLimitReached;
use Ajooda\AiMetering\Events\AiProviderCallFailed;
use Ajooda\AiMetering\Events\AiUsageRecorded;
use Ajooda\AiMetering\Exceptions\AiLimitExceededException;
use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Services\Billing\BillingDriver;
use Ajooda\AiMetering\Services\Providers\ManualProvider;
use Ajooda\AiMetering\Support\LimitCheckResult;
use Ajooda\AiMetering\Support\MeteredResponse;
use Ajooda\AiMetering\Support\ProviderUsage;
use Illuminate\Support\Facades\Log;

class AiMeter
{
    protected mixed $user = null;

    protected mixed $tenant = null;

    protected mixed $billable = null;

    protected ?string $provider = null;

    protected ?string $model = null;

    protected ?string $feature = null;

    protected ?string $billingMode = null;

    protected array $meta = [];

    protected ?string $idempotencyKey = null;

    protected ?ProviderUsage $manualUsage = null;

    public function __construct(
        protected UsageRecorder $usageRecorder,
        protected UsageLimiter $usageLimiter,
        protected CostCalculator $costCalculator,
        protected PlanResolver $planResolver,
        protected BillingDriver $billingDriver
    ) {}

    /**
     * Set the user for this usage.
     */
    public function forUser(mixed $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set the tenant for this usage.
     */
    public function forTenant(mixed $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * Set the billable entity (user or tenant).
     */
    public function billable(mixed $billable): self
    {
        $this->billable = $billable;

        return $this;
    }

    /**
     * Set the provider and model.
     */
    public function usingProvider(string $provider, string $model): self
    {
        $this->provider = $provider;
        $this->model = $model;

        return $this;
    }

    /**
     * Set the feature name.
     */
    public function feature(string $feature): self
    {
        $this->feature = $feature;

        return $this;
    }

    /**
     * Set the billing mode.
     */
    public function billingMode(string $mode): self
    {
        $this->billingMode = $mode;

        return $this;
    }

    /**
     * Add metadata.
     */
    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * Set idempotency key.
     */
    public function withIdempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    /**
     * Set manual usage data (for manual provider).
     */
    public function withManualUsage(array $usage): self
    {
        $this->manualUsage = ProviderUsage::fromArray($usage);

        return $this;
    }

    /**
     * Execute the AI call and record usage.
     */
    public function call(callable $callback): MeteredResponse
    {
        $billable = $this->resolveBillable();

        $lock = null;
        if ($billable && config('ai-metering.security.prevent_race_conditions', true)) {
            $lockKey = "ai-metering.lock.{$billable->getMorphClass()}.{$billable->id}";
            $lock = \Illuminate\Support\Facades\Cache::lock($lockKey, 10);

            if (! $lock->get()) {
                throw new \Ajooda\AiMetering\Exceptions\AiBillingException('Too many concurrent requests. Please try again.');
            }
        }

        try {
            $provider = $this->resolveProvider();

            $limitCheck = $this->checkLimits($billable);

            $this->enforceLimits($limitCheck);

            try {
                $result = $provider->call($callback);
                $response = $result['response'];
                $usage = $result['usage'];

                if ($this->manualUsage) {
                    $usage = $this->manualUsage;
                }

                $usage = $this->ensureCosts($usage);

                $this->recordUsage($billable, $usage);

                $this->handleBilling($billable, $usage, $limitCheck);

                $this->dispatchEvents($billable, $usage, $limitCheck);

                $this->clearCaches($billable);

                return new MeteredResponse($response, $usage, $limitCheck);
            } catch (\Exception $e) {
                event(new AiProviderCallFailed($billable, $this->provider, $this->model, $e));

                if (config('ai-metering.logging.log_failures', true)) {
                    Log::error('AI provider call failed', [
                        'billable_type' => $billable ? get_class($billable) : null,
                        'billable_id' => $billable?->id,
                        'provider' => $this->provider,
                        'model' => $this->model,
                        'error' => $e->getMessage(),
                    ]);
                }

                throw $e;
            }
        } finally {
            $lock?->release();
        }
    }

    /**
     * Resolve the billable entity.
     */
    protected function resolveBillable(): mixed
    {
        if ($this->billable) {
            return $this->billable;
        }

        if ($this->tenant) {
            return $this->tenant;
        }

        return $this->user;
    }

    /**
     * Resolve the provider client.
     */
    protected function resolveProvider(): ProviderClient
    {
        $provider = $this->provider ?? config('ai-metering.default_provider', 'openai');
        $model = $this->model ?? 'unknown';

        $factory = app(ProviderFactory::class);
        $providerClient = $factory->make($provider, $model);

        if ($provider === 'manual' && $this->manualUsage) {
            if ($providerClient instanceof ManualProvider) {
                $providerClient->setUsage($this->manualUsage->toArray());
            }
        }

        return $providerClient;
    }

    /**
     * Check limits for the billable entity.
     */
    protected function checkLimits(mixed $billable): LimitCheckResult
    {
        if (! $billable) {
            return LimitCheckResult::unlimited();
        }

        $requestedTokens = $this->manualUsage?->totalTokens;
        $requestedCost = $this->manualUsage?->totalCost;

        return $this->usageLimiter->checkLimit($billable, $requestedTokens, $requestedCost);
    }

    /**
     * Enforce limits based on configuration.
     */
    protected function enforceLimits(LimitCheckResult $limitCheck): void
    {
        $overageBehavior = config('ai-metering.billing.overage_behavior', 'block');

        if ($limitCheck->hardLimitReached && $overageBehavior === 'block') {
            throw new AiLimitExceededException(
                'AI usage limit exceeded. Remaining tokens: '.($limitCheck->remainingTokens ?? 0)
            );
        }
    }

    /**
     * Ensure costs are calculated for usage.
     */
    protected function ensureCosts(ProviderUsage $usage): ProviderUsage
    {
        if ($usage->totalCost !== null) {
            return $usage;
        }

        $costs = $this->costCalculator->calculate(
            $this->provider ?? config('ai-metering.default_provider', 'openai'),
            $this->model ?? 'unknown',
            $usage->inputTokens,
            $usage->outputTokens,
            $usage->totalTokens
        );

        return new ProviderUsage(
            inputTokens: $usage->inputTokens,
            outputTokens: $usage->outputTokens,
            totalTokens: $usage->totalTokens,
            inputCost: $costs['input_cost'],
            outputCost: $costs['output_cost'],
            totalCost: $costs['total_cost'],
            currency: $usage->currency ?? 'usd'
        );
    }

    /**
     * Record usage to the database.
     */
    protected function recordUsage(mixed $billable, ProviderUsage $usage): ?AiUsage
    {
        $data = [
            'billable_type' => $billable ? get_class($billable) : null,
            'billable_id' => $billable?->id,
            'user_id' => $this->user?->id,
            'tenant_id' => $this->tenant?->id ?? (is_string($this->tenant) ? $this->tenant : null),
            'provider' => $this->provider ?? config('ai-metering.default_provider', 'openai'),
            'model' => $this->model ?? 'unknown',
            'feature' => $this->feature,
            'input_tokens' => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
            'total_tokens' => $usage->totalTokens,
            'input_cost' => $usage->inputCost ?? 0.0,
            'output_cost' => $usage->outputCost ?? 0.0,
            'total_cost' => $usage->totalCost ?? 0.0,
            'currency' => $usage->currency ?? 'usd',
            'meta' => $this->meta,
            'idempotency_key' => $this->idempotencyKey,
            'occurred_at' => now(),
        ];

        return $this->usageRecorder->record($data);
    }

    /**
     * Handle billing
     */
    protected function handleBilling(mixed $billable, ProviderUsage $usage, LimitCheckResult $limitCheck): void
    {
        if (! $billable) {
            return;
        }

        try {
            $this->billingDriver->handleUsage(
                $billable,
                $usage->totalCost ?? 0.0,
                $usage->totalTokens ?? 0,
                $limitCheck,
                $usage->currency ?? 'usd'
            );
        } catch (\Exception $e) {
            if (config('ai-metering.logging.log_failures', true)) {
                Log::error('Billing driver failed', [
                    'billable_type' => get_class($billable),
                    'billable_id' => $billable->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $subscription = $this->planResolver->resolveSubscription($billable);
            if ($subscription?->billing_mode === 'credits') {
                throw $e;
            }
        }
    }

    /**
     * Dispatch events based on usage and limits.
     */
    protected function dispatchEvents(mixed $billable, ProviderUsage $usage, LimitCheckResult $limitCheck): void
    {
        if (! $billable) {
            return;
        }

        event(new AiUsageRecorded($billable, $usage, $this->provider, $this->model));

        if ($limitCheck->approaching) {
            event(new AiLimitApproaching($billable, $limitCheck));
        }

        if ($limitCheck->hardLimitReached) {
            event(new AiLimitReached($billable, $limitCheck));
        }
    }

    /**
     * Clear caches for the billable entity.
     */
    protected function clearCaches(mixed $billable): void
    {
        if (! $billable) {
            return;
        }

        $this->usageLimiter->clearCache($billable);
        $this->planResolver->clearCache($billable);
    }

    /**
     * Create a new instance for static calls.
     */
    public static function make(): self
    {
        return app(self::class);
    }
}
