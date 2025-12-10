<?php

namespace Ajooda\AiMetering\Services\Billing;

use Ajooda\AiMetering\Events\AiCreditsDeducted;
use Ajooda\AiMetering\Events\AiOverageCharged;
use Ajooda\AiMetering\Exceptions\AiCreditsInsufficientException;
use Ajooda\AiMetering\Models\AiCreditWallet;
use Ajooda\AiMetering\Models\AiOverage;
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Services\PlanResolver;
use Ajooda\AiMetering\Support\LimitCheckResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierBillingDriver implements BillingDriver
{
    public function __construct(
        protected PlanResolver $planResolver
    ) {}

    /**
     * Handle usage billing.
     */
    public function handleUsage(
        mixed $billable,
        float $cost,
        int $tokens,
        LimitCheckResult $limitResult,
        string $currency = 'usd'
    ): void {
        if (! $billable) {
            return;
        }

        $subscription = $this->planResolver->resolveSubscription($billable);

        if ($subscription && $subscription->billing_mode) {
            $billingMode = $subscription->billing_mode;
        } elseif (config('ai-metering.billing.driver') === CashierBillingDriver::class) {
            $billingMode = 'plan';
        } else {
            $billingMode = null;
        }

        if ($billingMode === 'credits') {
            $this->handleCreditsMode($billable, $cost, $tokens, $currency);
        } else {
            $this->handlePlanMode($billable, $cost, $tokens, $limitResult, $subscription, $currency);
        }
    }

    /**
     * Handle credits mode billing.
     */
    protected function handleCreditsMode(mixed $billable, float $cost, int $tokens, string $usageCurrency = 'usd'): void
    {
        $wallet = $this->getOrCreateWallet($billable);

        try {
            DB::transaction(function () use ($wallet, $cost, $tokens, $billable, $usageCurrency) {
                $wallet = AiCreditWallet::where('id', $wallet->id)->lockForUpdate()->first();

                if (! $wallet) {
                    throw new \RuntimeException('Wallet not found after locking');
                }

                $walletCurrency = $wallet->currency;
                $convertedCost = $this->convertCurrency($cost, $usageCurrency, $walletCurrency);

                if ($usageCurrency !== $walletCurrency && config('ai-metering.logging.enabled', true)) {
                    Log::info('Currency conversion applied', [
                        'billable_id' => $billable->id,
                        'original_cost' => $cost,
                        'original_currency' => $usageCurrency,
                        'converted_cost' => $convertedCost,
                        'wallet_currency' => $walletCurrency,
                    ]);
                }

                $overdraftAllowed = config('ai-metering.billing.credit_overdraft_allowed', false);

                if (! $overdraftAllowed && ! $wallet->hasSufficientBalance($convertedCost)) {
                    throw new AiCreditsInsufficientException(
                        "Insufficient credits. Required: {$convertedCost} {$walletCurrency}, Available: {$wallet->balance} {$walletCurrency}"
                    );
                }

                $wallet->deductCredits($convertedCost, 'usage', [
                    'tokens' => $tokens,
                    'provider' => 'ai-metering',
                    'original_cost' => $cost,
                    'original_currency' => $usageCurrency,
                ]);

                event(new AiCreditsDeducted($billable, $convertedCost, 'usage'));
            });
        } catch (\Exception $e) {
            if (config('ai-metering.logging.log_failures', true)) {
                Log::error('Billing driver failed', [
                    'billable_type' => get_class($billable),
                    'billable_id' => $billable->id,
                    'wallet_id' => $wallet->id ?? null,
                    'cost' => $cost,
                    'currency' => $usageCurrency,
                    'tokens' => $tokens,
                    'error' => $e->getMessage(),
                ]);
            }
            throw $e;
        }
    }

    /**
     * Handle plan mode billing
     */
    protected function handlePlanMode(
        mixed $billable,
        float $cost,
        int $tokens,
        LimitCheckResult $limitResult,
        ?AiSubscription $subscription,
        string $usageCurrency = 'usd'
    ): void {
        if (! $limitResult->hardLimitReached) {
            return;
        }

        if ($subscription?->plan && ! $subscription->plan->allowsOverage()) {
            return;
        }

        $overageBehavior = config('ai-metering.billing.overage_behavior', 'block');
        $overageSyncStrategy = config('ai-metering.billing.overage_sync_strategy', 'batch');

        if ($overageBehavior === 'charge') {
            $overageAmount = $this->calculateOverage($billable, $cost, $limitResult);

            if ($overageAmount > 0) {
                $billingCurrency = config('ai-metering.billing.currency', 'usd');
                $convertedOverageAmount = $this->convertCurrency($overageAmount, $usageCurrency, $billingCurrency);

                if ($usageCurrency !== $billingCurrency && config('ai-metering.logging.enabled', true)) {
                    Log::info('Overage currency conversion applied', [
                        'billable_id' => $billable->id,
                        'original_amount' => $overageAmount,
                        'original_currency' => $usageCurrency,
                        'converted_amount' => $convertedOverageAmount,
                        'billing_currency' => $billingCurrency,
                    ]);
                }

                if ($overageSyncStrategy === 'immediate') {
                    $this->syncOverageToStripe($billable, $convertedOverageAmount, $tokens, $billingCurrency);
                } else {
                    $this->storeOverage($billable, $convertedOverageAmount, $tokens, $billingCurrency);
                }

                event(new AiOverageCharged($billable, $convertedOverageAmount, $tokens, $billingCurrency));
            }
        }
    }

    /**
     * Calculate overage amount.
     */
    protected function calculateOverage(mixed $billable, float $cost, LimitCheckResult $limitResult): float
    {
        if ($limitResult->remainingCost !== null && $limitResult->remainingCost < $cost) {
            return $cost - $limitResult->remainingCost;
        }

        if ($limitResult->hardLimitReached) {
            return $cost;
        }

        return 0.0;
    }

    /**
     * Store overage in database for batch sync.
     *
     * @param  bool  $includeSynced  Whether to include already synced overages when aggregating
     */
    protected function storeOverage(mixed $billable, float $amount, int $tokens, ?string $currency = null, bool $includeSynced = false): AiOverage
    {
        $currency = $currency ?? config('ai-metering.billing.currency', 'usd');

        $period = \Ajooda\AiMetering\Support\Period::fromConfig(config('ai-metering.period', []));
        $periodStart = $period->getStart();
        $periodEnd = $period->getEnd();

        $query = AiOverage::where('billable_type', get_class($billable))
            ->where('billable_id', $billable->id)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd);

        if (! $includeSynced) {
            $query->whereNull('synced_at');
        }

        $existing = $query->first();

        if ($existing) {
            $existing->increment('tokens', $tokens);
            $existing->increment('cost', $amount);
            $existing->refresh();

            return $existing;
        }

        return AiOverage::create([
            'billable_type' => get_class($billable),
            'billable_id' => $billable->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'tokens' => $tokens,
            'cost' => $amount,
            'currency' => $currency,
        ]);
    }

    /**
     * Sync overage to Stripe immediately.
     */
    protected function syncOverageToStripe(mixed $billable, float $amount, int $tokens, ?string $currency = null): void
    {
        $currency = $currency ?? config('ai-metering.billing.currency', 'usd');

        if (! class_exists(\Laravel\Cashier\Cashier::class)) {
            if (config('ai-metering.logging.enabled', true)) {
                Log::warning('Laravel Cashier is not installed. Cannot sync overage to Stripe.');
            }
            $this->storeOverage($billable, $amount, $tokens, $currency);

            return;
        }

        try {
            if (! method_exists($billable, 'asStripeCustomer')) {
                if (config('ai-metering.logging.enabled', true)) {
                    Log::warning('Billable entity does not implement Cashier Billable interface.');
                }
                $this->storeOverage($billable, $amount, $tokens, $currency);

                return;
            }

            $stripeCustomer = $billable->asStripeCustomer();

            $idempotencyKey = 'ai-overage-'.hash('sha256', serialize([
                get_class($billable),
                $billable->id,
                $amount,
                $tokens,
                now()->toDateString(),
            ]));

            if (! class_exists(\Stripe\Stripe::class)) {
                if (config('ai-metering.logging.enabled', true)) {
                    Log::warning('Stripe PHP SDK is not installed. Cannot sync overage to Stripe.');
                }
                $this->storeOverage($billable, $amount, $tokens, $currency);

                return;
            }

            \Stripe\Stripe::setApiKey(config('cashier.secret'));

            $invoiceItem = \Stripe\InvoiceItem::create([
                'customer' => $stripeCustomer->id,
                'amount' => (int) ($amount * 100),
                'currency' => $currency,
                'description' => "AI Usage Overage - {$tokens} tokens",
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            $overage = $this->storeOverage($billable, $amount, $tokens, $currency, includeSynced: false);
            $overage->markAsSynced($invoiceItem->id);

            if (config('ai-metering.logging.enabled', true)) {
                Log::info('Overage synced to Stripe', [
                    'billable_type' => get_class($billable),
                    'billable_id' => $billable->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'overage_id' => $overage->id,
                    'amount' => $amount,
                    'currency' => $currency,
                ]);
            }
        } catch (\Exception $e) {
            if (config('ai-metering.logging.log_failures', true)) {
                Log::error('Failed to sync overage to Stripe', [
                    'billable_type' => get_class($billable),
                    'billable_id' => $billable->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->storeOverage($billable, $amount, $tokens, $currency);
        }
    }

    /**
     * Handle refund.
     */
    public function handleRefund(mixed $billable, float $amount, string $reason): void
    {
        if (! $billable) {
            return;
        }

        try {
            $subscription = $this->planResolver->resolveSubscription($billable);
            $billingMode = $subscription?->billing_mode ?? 'plan';

            if ($billingMode === 'credits') {
                $wallet = $this->getOrCreateWallet($billable);
                $wallet->addCredits($amount, $reason);

                if (config('ai-metering.logging.enabled', true)) {
                    Log::info('Credits refunded', [
                        'billable_type' => get_class($billable),
                        'billable_id' => $billable->id,
                        'amount' => $amount,
                        'reason' => $reason,
                    ]);
                }
            }

        } catch (\Exception $e) {
            if (config('ai-metering.logging.log_failures', true)) {
                Log::error('Refund failed', [
                    'billable_type' => get_class($billable),
                    'billable_id' => $billable->id,
                    'amount' => $amount,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                ]);
            }
            throw $e;
        }
    }

    /**
     * Get or create credit wallet for billable.
     */
    protected function getOrCreateWallet(mixed $billable): AiCreditWallet
    {
        return AiCreditWallet::firstOrCreate(
            [
                'billable_type' => get_class($billable),
                'billable_id' => $billable->id,
            ],
            [
                'balance' => 0,
                'currency' => config('ai-metering.billing.currency', 'usd'),
            ]
        );
    }

    /**
     * Convert currency amount from one currency to another.
     *
     * @param  float  $amount  The amount to convert
     * @param  string  $fromCurrency  Source currency code (e.g., 'usd', 'eur')
     * @param  string  $toCurrency  Target currency code (e.g., 'usd', 'eur')
     * @return float The converted amount
     */
    protected function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if (strtolower($fromCurrency) === strtolower($toCurrency)) {
            return $amount;
        }

        $rates = config('ai-metering.billing.currency_rates', []);

        $rateKey = strtolower("{$fromCurrency}_{$toCurrency}");
        if (isset($rates[$rateKey])) {
            return $amount * (float) $rates[$rateKey];
        }

        $reverseRateKey = strtolower("{$toCurrency}_{$fromCurrency}");
        if (isset($rates[$reverseRateKey])) {
            return $amount / (float) $rates[$reverseRateKey];
        }

        $usdBase = config('ai-metering.billing.currency', 'usd');
        if (strtolower($usdBase) === 'usd' && strtolower($fromCurrency) !== 'usd' && strtolower($toCurrency) !== 'usd') {
            $fromToUsd = $rates[strtolower("{$fromCurrency}_usd")] ?? null;
            $usdToTo = $rates[strtolower("usd_{$toCurrency}")] ?? null;

            if ($fromToUsd && $usdToTo) {
                return ($amount * (float) $fromToUsd) * (float) $usdToTo;
            }
        }

        if (config('ai-metering.logging.enabled', true)) {
            Log::warning('Currency conversion rate not found', [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'amount' => $amount,
                'available_rates' => array_keys($rates),
            ]);
        }

        return $amount;
    }
}
