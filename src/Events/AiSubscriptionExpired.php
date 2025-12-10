<?php

namespace Ajooda\AiMetering\Events;

use Ajooda\AiMetering\Models\AiSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiSubscriptionExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AiSubscription $subscription
    ) {}
}
