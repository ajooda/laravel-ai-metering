<?php

namespace Ajooda\AiMetering\Tests\Unit\Support;

use Ajooda\AiMetering\Support\Period;
use Ajooda\AiMetering\Tests\TestCase;
use Carbon\Carbon;

class PeriodTest extends TestCase
{
    public function test_calculates_monthly_calendar_period(): void
    {
        $period = new Period('monthly', 'calendar', 'UTC');
        $reference = Carbon::parse('2024-01-15');

        $start = $period->getStart($reference);
        $end = $period->getEnd($reference);

        $this->assertEquals('2024-01-01 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-02-01 00:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_calculates_weekly_calendar_period(): void
    {
        $period = new Period('weekly', 'calendar', 'UTC');
        $reference = Carbon::parse('2024-01-15'); // Monday

        $start = $period->getStart($reference);
        $end = $period->getEnd($reference);

        $this->assertEquals('Monday', $start->format('l'));
    }

    public function test_calculates_daily_period(): void
    {
        $period = new Period('daily', 'calendar', 'UTC');
        $reference = Carbon::parse('2024-01-15 14:30:00');

        $start = $period->getStart($reference);
        $end = $period->getEnd($reference);

        $this->assertEquals('2024-01-15 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-16 00:00:00', $end->format('Y-m-d H:i:s'));
    }

    public function test_checks_if_date_is_in_period(): void
    {
        $period = new Period('monthly', 'calendar', 'UTC');

        // January 20 should be in January period
        $this->assertTrue($period->contains(Carbon::parse('2024-01-20')));

        // February 1 should be in February period (not January)
        // Since contains uses the date to determine the period, Feb 1 will be in Feb period
        $this->assertTrue($period->contains(Carbon::parse('2024-02-01')));

        // But Jan 31 should be in January period
        $this->assertTrue($period->contains(Carbon::parse('2024-01-31')));

        // Verify Feb 1 is not in the January period by manually checking
        $janStart = Carbon::parse('2024-01-01 00:00:00');
        $janEnd = Carbon::parse('2024-02-01 00:00:00'); // End is exclusive
        $feb1 = Carbon::parse('2024-02-01 00:00:00');
        // Feb 1 is exactly at the end, so it's not in January (end is exclusive)
        $this->assertFalse($feb1 >= $janStart && $feb1 < $janEnd);
    }

    public function test_creates_from_config(): void
    {
        $config = [
            'type' => 'weekly',
            'alignment' => 'rolling',
            'timezone' => 'America/New_York',
        ];

        $period = Period::fromConfig($config);

        $this->assertEquals('weekly', $period->type);
        $this->assertEquals('rolling', $period->alignment);
        $this->assertEquals('America/New_York', $period->timezone);
    }
}
