<?php

namespace Ajooda\AiMetering\Contracts;

use Ajooda\AiMetering\Support\ProviderUsage;

interface ProviderClient
{
    /**
     * Execute the provider callback and extract usage information.
     *
     * @param  callable  $callback  The callback that actually calls the provider
     * @return array{response: mixed, usage: ProviderUsage}
     */
    public function call(callable $callback): array;
}
