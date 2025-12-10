# Credits

Credit-based billing allows users to purchase credits and use them for AI calls. This guide covers setting up credit wallets, managing credits, and using credit-based billing.

## Credit Wallets

Credit wallets store credit balances for billable entities.

### Creating Credit Wallets

```php
use Ajooda\AiMetering\Models\AiCreditWallet;

$wallet = AiCreditWallet::firstOrCreate(
    [
        'billable_type' => User::class,
        'billable_id' => $user->id,
    ],
    [
        'balance' => 0,
        'currency' => 'usd',
    ]
);
```

### Adding Credits

```php
$wallet->addCredits(100.00, 'top-up', [
    'payment_id' => 'pay_123',
    'payment_method' => 'stripe',
]);
```

Parameters:
- `amount`: Credit amount to add
- `reason`: Reason for adding credits (e.g., 'top-up', 'refund', 'bonus')
- `meta`: Optional metadata (array)

### Deducting Credits

```php
$wallet->deductCredits(50.00, 'usage', [
    'usage_id' => $usage->id,
    'feature' => 'chat',
]);
```

### Checking Balance

```php
if ($wallet->hasSufficientBalance(50.00)) {
    // Proceed with usage
}

$balance = $wallet->balance; // Current balance
```

### Credit Overdraft

Enable credit overdraft in config:

```php
'credit_overdraft_allowed' => env('AI_METERING_CREDIT_OVERDRAFT', false),
```

When enabled, negative balances are allowed.

## Using Credits Mode

Set billing mode to `credits` when metering:

```php
use Ajooda\AiMetering\Facades\AiMeter;

$user = auth()->user();

$response = AiMeter::forUser($user)
    ->billable($user)
    ->billingMode('credits')
    ->usingProvider('openai', 'gpt-4o-mini')
    ->call(fn () => OpenAI::chat()->create([...]));
```

Credits are automatically deducted from the wallet when usage is recorded.

## Credit Transactions

Credit transactions track all credit additions and deductions:

```php
use Ajooda\AiMetering\Models\AiCreditTransaction;

// Get all transactions for a wallet
$transactions = $wallet->transactions;

// Query transactions
$transactions = AiCreditTransaction::where('wallet_id', $wallet->id)
    ->where('type', 'credit') // or 'debit'
    ->get();
```

### Transaction Properties

- `type`: `'credit'` (addition) or `'debit'` (deduction)
- `amount`: Transaction amount
- `balance_after`: Balance after transaction
- `reason`: Reason for transaction
- `meta`: Optional metadata

## Credit Wallet Relationships

```php
$wallet->billable;      // MorphTo (User, Tenant, etc.)
$wallet->transactions;  // HasMany AiCreditTransaction
```

## Events

Listen to credit events:

```php
use Ajooda\AiMetering\Events\AiCreditsAdded;
use Ajooda\AiMetering\Events\AiCreditsDeducted;

Event::listen(AiCreditsAdded::class, function ($event) {
    // Handle credits added
    $wallet = $event->wallet;
    $amount = $event->amount;
});

Event::listen(AiCreditsDeducted::class, function ($event) {
    // Handle credits deducted
    $wallet = $event->wallet;
    $amount = $event->amount;
});
```

## Insufficient Credits

When credits are insufficient, `AiCreditsInsufficientException` is thrown:

```php
use Ajooda\AiMetering\Exceptions\AiCreditsInsufficientException;

try {
    $response = AiMeter::forUser($user)
        ->billingMode('credits')
        ->call(fn () => OpenAI::chat()->create([...]));
} catch (AiCreditsInsufficientException $e) {
    return response()->json([
        'error' => 'Insufficient credits',
        'balance' => $e->getBalance(),
        'required' => $e->getRequired(),
    ], 402);
}
```

## Integration with Payment Systems

### Stripe Integration

Example: Add credits after successful payment:

```php
use Laravel\Cashier\Payment;

// After successful payment
$wallet = AiCreditWallet::firstOrCreate(
    ['billable_type' => User::class, 'billable_id' => $user->id],
    ['balance' => 0, 'currency' => 'usd']
);

$wallet->addCredits($payment->amount / 100, 'stripe_payment', [
    'payment_intent_id' => $payment->id,
    'stripe_customer_id' => $user->stripe_id,
]);
```

### Manual Top-Up

Example: Admin adds credits manually:

```php
$wallet = AiCreditWallet::firstOrCreate(
    ['billable_type' => User::class, 'billable_id' => $user->id],
    ['balance' => 0, 'currency' => 'usd']
);

$wallet->addCredits(50.00, 'admin_grant', [
    'admin_id' => auth()->id(),
    'reason' => 'Promotional credits',
]);
```

## Best Practices

1. **Always check balance before usage**: Use `hasSufficientBalance()` before expensive operations
2. **Handle insufficient credits gracefully**: Catch `AiCreditsInsufficientException` and provide clear error messages
3. **Track credit sources**: Use metadata to track where credits came from (payments, refunds, bonuses, etc.)
4. **Monitor credit usage**: Query transactions regularly to monitor usage patterns
5. **Set minimum balance alerts**: Notify users when balance is low

## Next Steps

- [Billing Integration](billing.md) - Stripe/Cashier integration
- [Plans & Quotas](plans-and-quotas.md) - Plan-based billing
- [Usage Guide](../usage.md) - General usage patterns

