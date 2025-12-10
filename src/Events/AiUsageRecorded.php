<?php

namespace Ajooda\AiMetering\Events;

use Ajooda\AiMetering\Support\ProviderUsage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiUsageRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public ProviderUsage $usage,
        public ?string $provider,
        public ?string $model
    ) {}
}
