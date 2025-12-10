# Multi-Tenancy

Laravel AI Metering supports multi-tenancy without coupling to a specific tenancy package. This guide covers setting up tenant resolvers and using the package in multi-tenant applications.

## Tenant Resolver

The package uses a `TenantResolver` to determine the current tenant. By default, it uses `NullTenantResolver` (no tenancy).

### Creating a Custom Tenant Resolver

Create a tenant resolver that implements the `TenantResolver` interface:

```php
namespace App\Resolvers;

use Ajooda\AiMetering\Contracts\TenantResolver;

class CustomTenantResolver implements TenantResolver
{
    public function resolve(mixed $context = null): mixed
    {
        // Return the current tenant
        // This depends on your tenancy package
        
        // Example: Stancl/Tenancy
        return tenant();
        
        // Example: Spatie Laravel Multitenancy
        // return \Spatie\Multitenancy\Models\Tenant::current();
        
        // Example: Custom implementation
        // return app('tenant');
    }
}
```

### Registering the Tenant Resolver

Register it in `config/ai-metering.php`:

```php
'tenant_resolver' => \App\Resolvers\CustomTenantResolver::class,
```

Or via environment variable:

```env
AI_METERING_TENANT_RESOLVER=App\Resolvers\CustomTenantResolver
```

## Using with Tenants

### Setting Tenant Explicitly

Set the tenant explicitly when metering:

```php
use Ajooda\AiMetering\Facades\AiMeter;

$user = auth()->user();
$tenant = tenant(); // or get tenant from context

$response = AiMeter::forUser($user)
    ->forTenant($tenant)
    ->billable($tenant) // Bill the tenant, not the user
    ->usingProvider('openai', 'gpt-4o-mini')
    ->call(fn () => OpenAI::chat()->create([...]));
```

### Automatic Tenant Resolution

If you set up a tenant resolver, the package can automatically resolve the tenant from context. However, it's recommended to set it explicitly for clarity.

## Tenant as Billable Entity

In multi-tenant applications, you often want to bill the tenant rather than individual users:

```php
$response = AiMeter::forUser($user)      // Track which user made the call
    ->forTenant($tenant)                  // Track which tenant
    ->billable($tenant)                    // Bill the tenant
    ->usingProvider('openai', 'gpt-4o-mini')
    ->call(fn () => OpenAI::chat()->create([...]));
```

This allows:
- Tracking usage per user (for analytics)
- Billing the tenant (for billing)
- Enforcing limits per tenant (for quotas)

## Tenant-Scoped Queries

Query usage by tenant:

```php
use Ajooda\AiMetering\Models\AiUsage;

// Query usage for a tenant
$usage = AiUsage::where('tenant_id', $tenant->id)->get();

// Or if tenant is the billable entity
$usage = AiUsage::forBillable($tenant)->get();
```

## Tenant Plans and Subscriptions

Create plans and subscriptions for tenants:

```php
use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;

$plan = AiPlan::create([
    'name' => 'Enterprise Plan',
    'slug' => 'enterprise',
    'monthly_token_limit' => 10000000,
]);

$subscription = AiSubscription::create([
    'billable_type' => Tenant::class,
    'billable_id' => $tenant->id,
    'ai_plan_id' => $plan->id,
    'billing_mode' => 'plan',
    'started_at' => now(),
    'renews_at' => now()->addMonth(),
]);
```

## Integration Examples

### Stancl/Tenancy

```php
namespace App\Resolvers;

use Ajooda\AiMetering\Contracts\TenantResolver;
use Stancl\Tenancy\Facades\Tenancy;

class StanclTenantResolver implements TenantResolver
{
    public function resolve(mixed $context = null): mixed
    {
        return Tenancy::tenant();
    }
}
```

### Spatie Laravel Multitenancy

```php
namespace App\Resolvers;

use Ajooda\AiMetering\Contracts\TenantResolver;
use Spatie\Multitenancy\Models\Tenant;

class SpatieTenantResolver implements TenantResolver
{
    public function resolve(mixed $context = null): mixed
    {
        return Tenant::current();
    }
}
```

### Custom Implementation

```php
namespace App\Resolvers;

use Ajooda\AiMetering\Contracts\TenantResolver;

class CustomTenantResolver implements TenantResolver
{
    public function resolve(mixed $context = null): mixed
    {
        // Your custom tenant resolution logic
        if ($context instanceof Request) {
            return $context->user()->tenant;
        }
        
        return auth()->user()?->tenant;
    }
}
```

## Best Practices

1. **Set tenant explicitly**: Always set the tenant explicitly when metering for clarity
2. **Bill the tenant**: Use tenant as billable entity for billing
3. **Track user usage**: Use `forUser()` to track which user made the call
4. **Separate concerns**: Keep tenant resolution logic in a dedicated resolver class
5. **Test tenant isolation**: Ensure usage is properly isolated per tenant

## Next Steps

- [Plans & Quotas](plans-and-quotas.md) - Set up tenant plans
- [Billing Integration](billing.md) - Bill tenants via Stripe
- [Usage Guide](../usage.md) - General usage patterns

