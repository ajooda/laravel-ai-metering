<?php

namespace Ajooda\AiMetering\Events;

use Ajooda\AiMetering\Support\LimitCheckResult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiLimitReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public LimitCheckResult $limitCheck
    ) {}
}
