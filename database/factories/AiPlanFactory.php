<?php

namespace Ajooda\AiMetering\Database\Factories;

use Ajooda\AiMetering\Models\AiPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiPlanFactory extends Factory
{
    protected $model = AiPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' Plan',
            'slug' => $this->faker->slug(),
            'monthly_token_limit' => $this->faker->numberBetween(10000, 1000000),
            'monthly_cost_limit' => $this->faker->randomFloat(2, 10, 1000),
            'overage_price_per_1k_tokens' => $this->faker->randomFloat(6, 0.001, 0.1),
            'features' => null,
            'is_active' => true,
            'trial_days' => null,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'monthly_token_limit' => null,
            'monthly_cost_limit' => null,
        ]);
    }
}
