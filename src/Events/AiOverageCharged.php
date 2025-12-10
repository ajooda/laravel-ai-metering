<?php

namespace Ajooda\AiMetering\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiOverageCharged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public float $overageAmount,
        public int $overageTokens,
        public string $currency
    ) {}
}
