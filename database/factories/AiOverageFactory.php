<?php

namespace Ajooda\AiMetering\Database\Factories;

use Ajooda\AiMetering\Models\AiOverage;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiOverageFactory extends Factory
{
    protected $model = AiOverage::class;

    public function definition(): array
    {
        return [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'tokens' => $this->faker->numberBetween(1000, 10000),
            'cost' => $this->faker->randomFloat(2, 1, 100),
            'currency' => 'usd',
            'stripe_invoice_item_id' => null,
            'synced_at' => null,
        ];
    }
}
