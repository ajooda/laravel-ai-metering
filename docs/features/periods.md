# Period Configuration

Usage periods define how usage is tracked over time. This guide covers configuring period types, alignments, and using the Period class.

## Period Types

Configure period type in `config/ai-metering.php`:

```php
'period' => [
    'type' => env('AI_METERING_PERIOD_TYPE', 'monthly'), // 'monthly', 'weekly', 'daily', 'yearly', 'rolling'
],
```

### Available Period Types

- `monthly`: Calendar month (1st to last day) or rolling 30 days
- `weekly`: Calendar week (Monday-Sunday) or rolling 7 days
- `daily`: Calendar day (00:00-23:59) or rolling 24 hours
- `yearly`: Calendar year or rolling 365 days
- `rolling`: Rolling period from subscription start

## Period Alignment

Configure period alignment:

```php
'period' => [
    'alignment' => env('AI_METERING_PERIOD_ALIGNMENT', 'calendar'), // 'calendar' or 'rolling'
],
```

### Calendar Alignment

Periods align to calendar boundaries:
- Monthly: 1st of month to last day of month
- Weekly: Monday to Sunday
- Daily: 00:00 to 23:59
- Yearly: January 1st to December 31st

### Rolling Alignment

Periods start from a specific date and roll forward:
- Monthly: 30 days from start date
- Weekly: 7 days from start date
- Daily: 24 hours from start date
- Yearly: 365 days from start date

## Timezone Configuration

Set timezone for period calculations:

```php
'period' => [
    'timezone' => env('AI_METERING_TIMEZONE', 'UTC'),
],
```

Period boundaries are calculated in the specified timezone.

## Using the Period Class

The `Period` class provides utilities for working with periods:

```php
use Ajooda\AiMetering\Support\Period;

// Create period from config
$period = Period::fromConfig(config('ai-metering.period'));

// Get period start/end
$start = $period->getStart(); // Current period start
$end = $period->getEnd();     // Current period end (exclusive)

// Check if date is in period
if ($period->contains(Carbon::now())) {
    // Date is within current period
}

// Get next/previous periods
$nextPeriod = $period->getNext();
$previousPeriod = $period->getPrevious();
```

### Period for Specific Date

Get period for a specific date:

```php
use Ajooda\AiMetering\Support\Period;
use Carbon\Carbon;

$period = Period::fromConfig(config('ai-metering.period'));
$date = Carbon::parse('2024-03-15');

$periodStart = $period->getStartForDate($date);
$periodEnd = $period->getEndForDate($date);
```

### Period for Subscription

Get period for a subscription (rolling periods):

```php
use Ajooda\AiMetering\Models\AiSubscription;
use Ajooda\AiMetering\Support\Period;

$subscription = AiSubscription::find(1);
$period = Period::fromConfig(config('ai-metering.period'));

// Get period for subscription start date
$periodStart = $period->getStartForDate($subscription->started_at);
$periodEnd = $period->getEndForDate($subscription->started_at);
```

## Querying Usage by Period

Query usage within a period:

```php
use Ajooda\AiMetering\Models\AiUsage;
use Ajooda\AiMetering\Support\Period;
use Carbon\Carbon;

$period = Period::fromConfig(config('ai-metering.period'));

// Get current period
$start = $period->getStart();
$end = $period->getEnd();

// Query usage in period
$usage = AiUsage::forBillable($user)
    ->inPeriod($start, $end)
    ->get();

// Calculate total usage
$totalTokens = $usage->sum('total_tokens');
$totalCost = $usage->sum('total_cost');
```

## Period Examples

### Monthly Calendar Period

```php
'period' => [
    'type' => 'monthly',
    'alignment' => 'calendar',
    'timezone' => 'America/New_York',
],
```

Usage resets on the 1st of each month at midnight in New York timezone.

### Monthly Rolling Period

```php
'period' => [
    'type' => 'monthly',
    'alignment' => 'rolling',
    'timezone' => 'UTC',
],
```

Usage resets 30 days from subscription start date.

### Weekly Calendar Period

```php
'period' => [
    'type' => 'weekly',
    'alignment' => 'calendar',
    'timezone' => 'UTC',
],
```

Usage resets every Monday at midnight UTC.

### Daily Period

```php
'period' => [
    'type' => 'daily',
    'alignment' => 'calendar',
    'timezone' => 'UTC',
],
```

Usage resets every day at midnight UTC.

## Subscription-Based Periods

For rolling periods, the period is calculated from the subscription start date:

```php
$subscription = AiSubscription::create([
    'billable_type' => User::class,
    'billable_id' => $user->id,
    'ai_plan_id' => $plan->id,
    'started_at' => Carbon::parse('2024-03-15 10:30:00'),
    'renews_at' => Carbon::parse('2024-03-15 10:30:00')->addMonth(),
]);

// For rolling monthly period:
// Period 1: 2024-03-15 10:30:00 to 2024-04-15 10:30:00
// Period 2: 2024-04-15 10:30:00 to 2024-05-15 10:30:00
```

## Best Practices

1. **Use calendar alignment for simplicity**: Calendar alignment is easier to understand and debug
2. **Use rolling alignment for fairness**: Rolling alignment ensures fair usage distribution
3. **Set appropriate timezone**: Use your primary user base timezone
4. **Document period configuration**: Document your period configuration for clarity
5. **Test period boundaries**: Test that usage resets correctly at period boundaries

## Next Steps

- [Plans & Quotas](plans-and-quotas.md) - Set up usage limits
- [Usage Guide](../usage.md) - General usage patterns
- [Advanced Topics](../advanced.md) - Advanced period usage

