<?php

namespace Ajooda\AiMetering\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Ajooda\AiMetering\Services\AiMeter forUser(mixed $user)
 * @method static \Ajooda\AiMetering\Services\AiMeter forTenant(mixed $tenant)
 * @method static \Ajooda\AiMetering\Services\AiMeter billable(mixed $billable)
 * @method static \Ajooda\AiMetering\Services\AiMeter usingProvider(string $provider, string $model)
 * @method static \Ajooda\AiMetering\Services\AiMeter feature(string $feature)
 * @method static \Ajooda\AiMetering\Services\AiMeter billingMode(string $mode)
 * @method static \Ajooda\AiMetering\Services\AiMeter withMeta(array $meta)
 * @method static \Ajooda\AiMetering\Services\AiMeter withIdempotencyKey(string $key)
 * @method static \Ajooda\AiMetering\Services\AiMeter withManualUsage(array $usage)
 * @method static \Ajooda\AiMetering\Support\MeteredResponse call(callable $callback)
 *
 * @see \Ajooda\AiMetering\Services\AiMeter
 */
class AiMeter extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Ajooda\AiMetering\Services\AiMeter::class;
    }
}
