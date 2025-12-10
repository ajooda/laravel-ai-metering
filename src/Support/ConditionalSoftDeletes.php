<?php

namespace Ajooda\AiMetering\Support;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait that conditionally applies SoftDeletes based on config.
 *
 *
 * Note: Migrations should conditionally add the deleted_at column based on config.
 */
trait ConditionalSoftDeletes
{
    use SoftDeletes {
        SoftDeletes::bootSoftDeletes as bootSoftDeletesTrait;
    }

    /**
     * Boot the soft deletes trait conditionally.
     */
    public static function bootSoftDeletes()
    {
        if (config('ai-metering.features.soft_deletes', false)) {
            static::bootSoftDeletesTrait();
        }
    }

    /**
     * Perform the actual delete query on this model instance.
     */
    public function performDeleteOnModel()
    {
        if (! config('ai-metering.features.soft_deletes', false)) {
            $this->forceDeleting = true;

            return $this->newModelQuery()->where($this->getKeyName(), $this->getKey())->delete();
        }

        return parent::performDeleteOnModel();
    }
}
