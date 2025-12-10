<?php

namespace Ajooda\AiMetering\Services\Providers;

use Ajooda\AiMetering\Contracts\ProviderClient;
use Ajooda\AiMetering\Support\ProviderUsage;

class ManualProvider implements ProviderClient
{
    protected ?ProviderUsage $manualUsage = null;

    /**
     * Set manual usage data.
     */
    public function setUsage(array $usage): self
    {
        $this->manualUsage = ProviderUsage::fromArray($usage);

        return $this;
    }

    /**
     * Execute the callback and return usage information.
     */
    public function call(callable $callback): array
    {
        $response = $callback();

        $usage = $this->manualUsage ?? new ProviderUsage;

        return [
            'response' => $response,
            'usage' => $usage,
        ];
    }

    /**
     * Create a new instance with usage data.
     */
    public static function withUsage(array $usage): self
    {
        return (new self)->setUsage($usage);
    }
}
