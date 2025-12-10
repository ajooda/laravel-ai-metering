<?php

namespace Ajooda\AiMetering\Events;

use Ajooda\AiMetering\Models\AiPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiPlanChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public ?AiPlan $oldPlan,
        public ?AiPlan $newPlan
    ) {}
}
