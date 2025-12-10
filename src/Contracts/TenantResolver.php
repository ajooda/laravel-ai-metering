<?php

namespace Ajooda\AiMetering\Contracts;

/**
 * Interface for resolving the current tenant.
 */
interface TenantResolver
{
    /**
     * Resolve the current tenant.
     *
     * @param  mixed  $context  Optional context for resolution
     * @return mixed The tenant instance or null
     */
    public function resolve(mixed $context = null): mixed;
}
