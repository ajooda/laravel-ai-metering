<?php

namespace Ajooda\AiMetering\Database\Factories;

use Ajooda\AiMetering\Models\AiUsageLimitOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiUsageLimitOverrideFactory extends Factory
{
    protected $model = AiUsageLimitOverride::class;

    public function definition(): array
    {
        return [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'token_limit' => $this->faker->numberBetween(10000, 100000),
            'cost_limit' => $this->faker->randomFloat(2, 10, 1000),
        ];
    }
}
