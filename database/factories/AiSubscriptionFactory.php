<?php

namespace Ajooda\AiMetering\Database\Factories;

use Ajooda\AiMetering\Models\AiPlan;
use Ajooda\AiMetering\Models\AiSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiSubscriptionFactory extends Factory
{
    protected $model = AiSubscription::class;

    public function definition(): array
    {
        return [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'ai_plan_id' => AiPlan::factory(),
            'billing_mode' => 'plan',
            'renews_at' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => null,
            'trial_ends_at' => null,
            'grace_period_ends_at' => null,
            'previous_plan_id' => null,
            'meta' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'ends_at' => now()->subDay(),
        ]);
    }

    public function credits(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_mode' => 'credits',
        ]);
    }
}
