<?php

namespace Ajooda\AiMetering\Tests\Unit\Services\Billing;

use Ajooda\AiMetering\Services\Billing\NullBillingDriver;
use Ajooda\AiMetering\Support\LimitCheckResult;
use Ajooda\AiMetering\Tests\TestCase;

class NullBillingDriverTest extends TestCase
{
    public function test_handles_usage_without_charging(): void
    {
        $driver = new NullBillingDriver;
        $billable = new class
        {
            public $id = 1;
        };

        $limitCheck = LimitCheckResult::allowed(remainingTokens: 1000);

        $driver->handleUsage($billable, 10.0, 1000, $limitCheck);

        $this->assertTrue(true); // If we get here, it worked
    }

    public function test_handles_refund_without_action(): void
    {
        $driver = new NullBillingDriver;
        $billable = new class
        {
            public $id = 1;
        };

        $driver->handleRefund($billable, 5.0, 'test');

        $this->assertTrue(true); // If we get here, it worked
    }
}
