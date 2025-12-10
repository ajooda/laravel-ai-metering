<?php

namespace Ajooda\AiMetering\Database\Factories;

use Ajooda\AiMetering\Models\AiUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiUsageFactory extends Factory
{
    protected $model = AiUsage::class;

    public function definition(): array
    {
        return [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'user_id' => null,
            'tenant_id' => null,
            'provider' => $this->faker->randomElement(['openai', 'anthropic']),
            'model' => $this->faker->randomElement(['gpt-4o-mini', 'gpt-4o', 'claude-3-5-sonnet']),
            'feature' => $this->faker->optional()->word(),
            'input_tokens' => $this->faker->numberBetween(100, 1000),
            'output_tokens' => $this->faker->numberBetween(100, 2000),
            'total_tokens' => function (array $attributes) {
                return ($attributes['input_tokens'] ?? 0) + ($attributes['output_tokens'] ?? 0);
            },
            'input_cost' => $this->faker->randomFloat(6, 0.0001, 0.01),
            'output_cost' => $this->faker->randomFloat(6, 0.0001, 0.01),
            'total_cost' => function (array $attributes) {
                return ($attributes['input_cost'] ?? 0) + ($attributes['output_cost'] ?? 0);
            },
            'currency' => 'usd',
            'meta' => null,
            'idempotency_key' => null,
            'occurred_at' => now(),
        ];
    }
}
