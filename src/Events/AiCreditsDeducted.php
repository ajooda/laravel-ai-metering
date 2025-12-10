<?php

namespace Ajooda\AiMetering\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiCreditsDeducted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public float $amount,
        public string $reason
    ) {}
}
