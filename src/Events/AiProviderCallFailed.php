<?php

namespace Ajooda\AiMetering\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiProviderCallFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public ?string $provider,
        public ?string $model,
        public \Throwable $exception
    ) {}
}
