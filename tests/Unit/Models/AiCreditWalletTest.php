<?php

namespace Ajooda\AiMetering\Tests\Unit\Models;

use Ajooda\AiMetering\Models\AiCreditTransaction;
use Ajooda\AiMetering\Models\AiCreditWallet;
use Ajooda\AiMetering\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AiCreditWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_add_credits(): void
    {
        $wallet = AiCreditWallet::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'balance' => 100.00,
            'currency' => 'usd',
        ]);

        $transaction = $wallet->addCredits(50.00, 'top-up', ['payment_id' => 'pay_123']);

        $this->assertEquals(150.00, $wallet->fresh()->balance);
        $this->assertInstanceOf(AiCreditTransaction::class, $transaction);
        $this->assertTrue($transaction->isCredit());
        $this->assertEquals(50.00, $transaction->amount);
    }

    public function test_can_deduct_credits(): void
    {
        $wallet = AiCreditWallet::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'balance' => 100.00,
            'currency' => 'usd',
        ]);

        $transaction = $wallet->deductCredits(30.00, 'usage', ['tokens' => 1000]);

        $this->assertEquals(70.00, $wallet->fresh()->balance);
        $this->assertInstanceOf(AiCreditTransaction::class, $transaction);
        $this->assertTrue($transaction->isDebit());
        $this->assertEquals(30.00, $transaction->amount);
    }

    public function test_checks_sufficient_balance(): void
    {
        $wallet = AiCreditWallet::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'balance' => 100.00,
            'currency' => 'usd',
        ]);

        $this->assertTrue($wallet->hasSufficientBalance(50.00));
        $this->assertTrue($wallet->hasSufficientBalance(100.00));
        $this->assertFalse($wallet->hasSufficientBalance(150.00));
    }

    public function test_creates_transaction_records(): void
    {
        $wallet = AiCreditWallet::create([
            'billable_type' => 'App\Models\User',
            'billable_id' => 1,
            'balance' => 100.00,
            'currency' => 'usd',
        ]);

        $wallet->addCredits(25.00, 'refund');
        $wallet->deductCredits(10.00, 'usage');

        $this->assertEquals(2, $wallet->transactions()->count());
        $this->assertEquals(1, $wallet->transactions()->where('direction', 'credit')->count());
        $this->assertEquals(1, $wallet->transactions()->where('direction', 'debit')->count());
    }
}
