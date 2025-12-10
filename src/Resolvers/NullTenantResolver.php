<?php

namespace Ajooda\AiMetering\Resolvers;

use Ajooda\AiMetering\Contracts\TenantResolver;

/**
 * Null tenant resolver that returns null (for apps without tenancy).
 */
class NullTenantResolver implements TenantResolver
{
    public function resolve(mixed $context = null): mixed
    {
        return null;
    }
}
