# Troubleshooting

Common issues and solutions when using Laravel AI Metering.

## Top 3 Most Common Issues

### 1. "Provider 'xxx' is not configured"

**Error**: `Provider 'xxx' is not configured`

**Solution**: Add the provider to `config/ai-metering.php`:

```php
'providers' => [
    'xxx' => [
        'class' => \Your\Provider\Class::class,
        'models' => [
            'model-name' => [
                'input_price_per_1k' => 0.001,
                'output_price_per_1k' => 0.002,
            ],
        ],
    ],
],
```

### 2. Usage not being recorded

**Symptoms**: Usage is not appearing in the database

**Quick checks**:
1. Verify queue worker is running if `AI_METERING_QUEUE_RECORDING` is enabled:
   ```bash
   php artisan queue:work
   ```
2. Check database connection:
   ```bash
   php artisan ai-metering:validate
   ```
3. Review logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```
4. Ensure idempotency keys are unique (if using)
5. Check that migrations have been run:
   ```bash
   php artisan migrate:status
   ```

**Solution**: If queue recording is enabled, ensure your queue worker is running. If not, check logs for errors.

### 3. Webhooks not working

**Symptoms**: Stripe webhooks are not updating subscriptions

**Solution**: Ensure Stripe IDs are synced to `AiSubscription.meta`:

1. Auto-sync is enabled by default (requires Cashier)
2. Or manually set:
   ```php
   $subscription->update([
       'meta' => [
           'stripe_subscription_id' => 'sub_xxx',
           'stripe_customer_id' => 'cus_xxx',
       ],
   ]);
   ```
3. Verify webhook endpoint is configured in Stripe dashboard
4. Check webhook logs in Stripe dashboard

See [Billing Integration](features/billing.md#webhook-handling) for details.

## Health Check

Run diagnostics:

```bash
php artisan ai-metering:validate
```

This validates:
- Configuration validity
- Database connectivity
- Data integrity
- Orphaned records

## Debug Mode

Enable detailed logging:

```php
'logging' => [
    'enabled' => true,
    'level' => 'debug',
    'log_failures' => true,
],
```

Check logs:

```bash
tail -f storage/logs/laravel.log
```

## Common Issues

### Limits Not Enforcing

**Symptoms**: Usage limits are not being enforced

**Possible causes**:
1. No active subscription for billable entity
2. Subscription is expired (expired subscriptions don't enforce limits)
3. Plan has unlimited limits (`null` values)
4. Overage behavior is set to `allow`

**Solution**:
1. Check subscription status:
   ```php
   $subscription = AiSubscription::where('billable_id', $user->id)->first();
   if ($subscription && $subscription->isActive()) {
       // Subscription is active
   }
   ```
2. Verify plan limits:
   ```php
   $plan = $subscription->plan;
   echo "Token limit: " . $plan->monthly_token_limit;
   echo "Cost limit: " . $plan->monthly_cost_limit;
   ```
3. Check overage behavior in config

### Credits Not Deducting

**Symptoms**: Credits are not being deducted from wallet

**Possible causes**:
1. Billing mode is not set to `credits`
2. Credit wallet doesn't exist
3. Exception is being caught silently

**Solution**:
1. Ensure billing mode is set:
   ```php
   AiMeter::forUser($user)
       ->billingMode('credits')
       ->call(...);
   ```
2. Create wallet if it doesn't exist:
   ```php
   AiCreditWallet::firstOrCreate(
       ['billable_type' => User::class, 'billable_id' => $user->id],
       ['balance' => 0, 'currency' => 'usd']
   );
   ```
3. Check for exceptions in logs

### Middleware Not Working

**Symptoms**: `ai.quota` middleware is not enforcing limits

**Possible causes**:
1. Middleware is not registered
2. Route is not using the middleware
3. Exception is being caught

**Solution**:
1. Verify middleware is registered (should be automatic):
   ```php
   // Check in routes/web.php or routes/api.php
   Route::middleware(['auth', 'ai.quota'])->group(function () {
       Route::post('/ai/chat', [ChatController::class, 'chat']);
   });
   ```
2. Check middleware alias:
   ```php
   // Should be registered automatically by service provider
   'ai.quota' => \Ajooda\AiMetering\Http\Middleware\EnforceAiQuota::class
   ```
3. Check exception handling in your exception handler

### Overages Not Syncing to Stripe

**Symptoms**: Overages are created but not synced to Stripe

**Possible causes**:
1. Sync strategy is set to `batch` but command is not running
2. Stripe API keys are not configured
3. Billing driver is not set to `CashierBillingDriver`

**Solution**:
1. Run sync command:
   ```bash
   php artisan ai-metering:sync-stripe-overages
   ```
2. Or set sync strategy to `immediate`:
   ```php
   'overage_sync_strategy' => 'immediate',
   ```
3. Verify Stripe API keys in `.env`:
   ```env
   STRIPE_KEY=sk_test_...
   STRIPE_SECRET=sk_test_...
   ```
4. Check billing driver:
   ```php
   'billing' => [
       'driver' => \Ajooda\AiMetering\Services\Billing\CashierBillingDriver::class,
   ],
   ```

### Period Calculation Issues

**Symptoms**: Usage is not resetting at period boundaries

**Possible causes**:
1. Timezone mismatch
2. Period alignment is incorrect
3. Subscription start date is not set correctly

**Solution**:
1. Check timezone configuration:
   ```php
   'period' => [
       'timezone' => 'America/New_York', // Use your primary timezone
   ],
   ```
2. Verify period type and alignment:
   ```php
   'period' => [
       'type' => 'monthly',
       'alignment' => 'calendar', // or 'rolling'
   ],
   ```
3. Check subscription start date:
   ```php
   $subscription->started_at; // Should be set correctly
   ```

### Database Connection Issues

**Symptoms**: Database errors or connection failures

**Solution**:
1. Verify database connection in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```
2. Test connection:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```
3. Check if using custom connection:
   ```php
   'storage' => [
       'connection' => 'custom_connection', // or null for default
   ],
   ```

## Getting More Help

### Search Existing Issues

- [GitHub Issues](https://github.com/ajooda/laravel-ai-metering/issues) - Search existing issues
- [GitHub Discussions](https://github.com/ajooda/laravel-ai-metering/discussions) - Ask questions

### Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue logs (if using queues)
tail -f storage/logs/queue.log
```

### Enable Debug Mode

```php
'logging' => [
    'enabled' => true,
    'level' => 'debug',
    'log_failures' => true,
],
```

### Report Issues

When reporting issues, include:
1. Laravel version
2. PHP version
3. Package version
4. Error message and stack trace
5. Relevant configuration (sanitized)
6. Steps to reproduce

## Next Steps

- [Configuration](configuration.md) - Review configuration options
- [Usage Guide](usage.md) - Review usage patterns
- [Advanced Topics](advanced.md) - Advanced troubleshooting

