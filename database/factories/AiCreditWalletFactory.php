<?php

namespace Ajooda\AiMetering\Database\Factories;

use Ajooda\AiMetering\Models\AiCreditWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiCreditWalletFactory extends Factory
{
    protected $model = AiCreditWallet::class;

    public function definition(): array
    {
        return [
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'balance' => $this->faker->randomFloat(2, 0, 1000),
            'currency' => 'usd',
            'meta' => null,
        ];
    }
}
