# Usage Guide

This guide covers how to use Laravel AI Metering to track AI usage in your application.

## Basic Usage

### Metering AI Calls

The package provides a fluent API via the `AiMeter` facade. Wrap your AI provider calls:

```php
use Ajooda\AiMetering\Facades\AiMeter;
use OpenAI\Laravel\Facades\OpenAI;

$response = AiMeter::forUser(auth()->user())
    ->billable(auth()->user())
    ->usingProvider('openai', 'gpt-4o-mini')
    ->feature('chat')
    ->call(function () {
        return OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ]);
    });

// Access the AI response
$aiResponse = $response->getResponse();

// Access usage information
$usage = $response->getUsage();
echo "Tokens: {$usage->totalTokens}, Cost: \${$usage->totalCost}";
```

### Understanding MeteredResponse

The `call()` method returns a `MeteredResponse` object with:

- `getResponse()`: Original provider response
- `getUsage()`: `ProviderUsage` object with token and cost data
- `getLimitCheck()`: `LimitCheckResult` with limit status
- `getRemainingTokens()`: Remaining tokens in period (or null if unlimited)
- `getRemainingCost()`: Remaining cost in period (or null if unlimited)
- `isApproachingLimit()`: Whether usage exceeds 80% of limit
- `isLimitReached()`: Whether hard limit is reached

## Fluent API Methods

### Setting User

```php
AiMeter::forUser($user)
```

Sets the user for usage tracking. The user is stored in the `ai_usages` table for analytics.

### Setting Tenant

```php
AiMeter::forTenant($tenant)
```

Sets the tenant for multi-tenant applications. Optional.

### Setting Billable Entity

```php
AiMeter::billable($billable)
```

Sets the billable entity (user, tenant, organization, etc.). Usage limits and billing are applied to this entity.

### Setting Provider and Model

```php
AiMeter::usingProvider('openai', 'gpt-4o-mini')
```

Sets the AI provider and model. Must match a configured provider in `config/ai-metering.php`.

### Setting Feature

```php
AiMeter::feature('email_reply')
```

Sets the feature name for categorization. Feature names must be alphanumeric with underscores.

### Setting Billing Mode

```php
AiMeter::billingMode('credits') // or 'plan'
```

Sets the billing mode:
- `plan`: Usage is tracked against plan limits
- `credits`: Usage is deducted from credit wallet

### Adding Metadata

```php
AiMeter::withMeta([
    'ticket_id' => $ticket->id,
    'customer_id' => $customer->id,
])
```

Adds metadata for tracking. Metadata is stored as JSON and can be used for filtering and analytics.

### Idempotency

```php
AiMeter::withIdempotencyKey('unique-request-id-123')
```

Prevents duplicate usage records for the same request. Useful for retries.

### Manual Usage

For providers that don't return usage automatically:

```php
AiMeter::withManualUsage([
    'input_tokens' => 450,
    'output_tokens' => 900,
    'total_tokens' => 1350,
])
```

## Common Workflows

### Controller Example

Complete controller example with error handling:

```php
namespace App\Http\Controllers;

use Ajooda\AiMetering\Facades\AiMeter;
use Ajooda\AiMetering\Exceptions\AiLimitExceededException;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate(['message' => 'required|string']);

        try {
            $response = AiMeter::forUser(auth()->user())
                ->billable(auth()->user())
                ->usingProvider('openai', 'gpt-4o-mini')
                ->feature('chat')
                ->call(function () use ($request) {
                    return OpenAI::chat()->create([
                        'model' => 'gpt-4o-mini',
                        'messages' => [
                            ['role' => 'user', 'content' => $request->message],
                        ],
                    ]);
                });

            return response()->json([
                'message' => $response->getResponse()->choices[0]->message->content,
                'usage' => [
                    'tokens' => $response->getUsage()->totalTokens,
                    'cost' => $response->getUsage()->totalCost,
                    'remaining_tokens' => $response->getRemainingTokens(),
                ],
            ]);
        } catch (AiLimitExceededException $e) {
            return response()->json([
                'error' => 'Usage limit exceeded',
                'message' => $e->getMessage(),
            ], 429);
        }
    }
}
```

### Using Middleware

Protect routes with quota enforcement:

```php
// routes/web.php
Route::middleware(['auth', 'ai.quota'])->group(function () {
    Route::post('/ai/chat', [ChatController::class, 'chat']);
});
```

The middleware automatically:
- Checks usage limits before allowing the request
- Returns 429 (Too Many Requests) if limit exceeded
- Adds response headers: `X-Remaining-Tokens`, `X-Remaining-Cost`, `X-Usage-Percentage`

### Manual Usage Tracking

Track usage when provider doesn't return usage data:

```php
$response = AiMeter::forUser($user)
    ->billable($user)
    ->usingProvider('manual', 'custom-model')
    ->withManualUsage([
        'input_tokens' => 450,
        'output_tokens' => 900,
        'total_tokens' => 1350,
    ])
    ->call(function () use ($customClient) {
        return $customClient->generate();
    });
```

### Batch Usage Recording

For high-volume scenarios, record multiple usages efficiently:

```php
use Ajooda\AiMetering\Services\UsageRecorder;

$recorder = app(UsageRecorder::class);

$usages = [
    [
        'billable_type' => User::class,
        'billable_id' => $user->id,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'total_tokens' => 100,
        'total_cost' => 0.1,
        'occurred_at' => now(),
    ],
    // ... more usage records
];

$recorded = $recorder->recordBatch($usages);
```

## Error Handling

The package throws specific exceptions:

```php
use Ajooda\AiMetering\Exceptions\AiLimitExceededException;
use Ajooda\AiMetering\Exceptions\AiCreditsInsufficientException;
use Ajooda\AiMetering\Exceptions\AiProviderException;

try {
    $response = AiMeter::forUser($user)->call(fn () => OpenAI::chat()->create([...]));
} catch (AiLimitExceededException $e) {
    // Handle limit exceeded
    return response()->json(['error' => 'Usage limit exceeded'], 429);
} catch (AiCreditsInsufficientException $e) {
    // Handle insufficient credits
    return response()->json(['error' => 'Insufficient credits'], 402);
} catch (AiProviderException $e) {
    // Handle provider errors
    return response()->json(['error' => 'AI provider error'], 500);
}
```

## Querying Usage

Use Eloquent scopes to query usage records:

```php
use Ajooda\AiMetering\Models\AiUsage;
use Carbon\Carbon;

// Query by billable entity
$usage = AiUsage::forBillable($user)->get();

// Query by provider
$usage = AiUsage::byProvider('openai')->get();

// Query by model
$usage = AiUsage::byModel('gpt-4o-mini')->get();

// Query by feature
$usage = AiUsage::byFeature('support_reply')->get();

// Query within a period
$start = Carbon::now()->startOfMonth();
$end = Carbon::now()->endOfMonth();
$usage = AiUsage::inPeriod($start, $end)->get();

// Combine scopes
$usage = AiUsage::forBillable($user)
    ->byProvider('openai')
    ->byFeature('email_reply')
    ->inPeriod($start, $end)
    ->get();
```

## Next Steps

- [Plans & Quotas](features/plans-and-quotas.md) - Set up usage limits
- [Credits](features/credits.md) - Credit-based billing
- [Billing Integration](features/billing.md) - Stripe/Cashier integration
- [Multi-Tenancy](features/multi-tenancy.md) - Multi-tenant usage tracking
- [Events](advanced.md#events) - Listen to usage events

