<?php

namespace Ajooda\AiMetering\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Period
{
    public function __construct(
        public readonly string $type, // 'monthly', 'weekly', 'daily', 'yearly', 'rolling'
        public readonly string $alignment, // 'calendar' or 'rolling'
        public readonly string $timezone = 'UTC'
    ) {}

    /**
     * Get the start of the period for the given reference date.
     */
    public function getStart(?CarbonInterface $reference = null): Carbon
    {
        $reference = $reference ? Carbon::parse($reference)->setTimezone($this->timezone) : Carbon::now($this->timezone);

        return match ($this->type) {
            'monthly' => $this->alignment === 'calendar'
                ? $reference->copy()->startOfMonth()
                : $reference->copy()->subDays($reference->day - 1)->startOfDay(),
            'weekly' => $this->alignment === 'calendar'
                ? $reference->copy()->startOfWeek()
                : $reference->copy()->subDays($reference->dayOfWeek)->startOfDay(),
            'daily' => $reference->copy()->startOfDay(),
            'yearly' => $this->alignment === 'calendar'
                ? $reference->copy()->startOfYear()
                : $reference->copy()->subDays($reference->dayOfYear - 1)->startOfDay(),
            'rolling' => $reference->copy(),
            default => $reference->copy()->startOfMonth(),
        };
    }

    /**
     * Get the end of the period for the given reference date.
     */
    public function getEnd(?CarbonInterface $reference = null): Carbon
    {
        $reference = $reference ? Carbon::parse($reference)->setTimezone($this->timezone) : Carbon::now($this->timezone);

        return match ($this->type) {
            'monthly' => $this->alignment === 'calendar'
                ? $reference->copy()->endOfMonth()->addSecond()
                : $this->getStart($reference)->copy()->addMonth()->subSecond(),
            'weekly' => $this->alignment === 'calendar'
                ? $reference->copy()->endOfWeek()->addSecond()
                : $this->getStart($reference)->copy()->addWeek()->subSecond(),
            'daily' => $reference->copy()->endOfDay()->addSecond(),
            'yearly' => $this->alignment === 'calendar'
                ? $reference->copy()->endOfYear()->addSecond()
                : $this->getStart($reference)->copy()->addYear()->subSecond(),
            'rolling' => match ($this->alignment) {
                'calendar' => $this->getStart($reference)->copy()->addMonth(),
                default => $this->getStart($reference)->copy()->addDays(30),
            },
            default => $reference->copy()->endOfMonth()->addSecond(),
        };
    }

    /**
     * Check if the given date falls within this period.
     * Uses the date itself to determine which period to check.
     */
    public function contains(CarbonInterface $date): bool
    {
        $date = Carbon::parse($date)->setTimezone($this->timezone);
        $start = $this->getStart($date);
        $end = $this->getEnd($date);

        return $date >= $start && $date < $end;
    }

    /**
     * Get the next period.
     */
    public function getNext(?CarbonInterface $reference = null): Period
    {
        $reference = $reference ? Carbon::parse($reference)->setTimezone($this->timezone) : Carbon::now($this->timezone);
        $end = $this->getEnd($reference);

        return new self($this->type, $this->alignment, $this->timezone);
    }

    /**
     * Get the previous period.
     */
    public function getPrevious(?CarbonInterface $reference = null): Period
    {
        $reference = $reference ? Carbon::parse($reference)->setTimezone($this->timezone) : Carbon::now($this->timezone);
        $start = $this->getStart($reference);

        return new self($this->type, $this->alignment, $this->timezone);
    }

    /**
     * Create a period from config.
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            $config['type'] ?? 'monthly',
            $config['alignment'] ?? 'calendar',
            $config['timezone'] ?? 'UTC'
        );
    }
}
