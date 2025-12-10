<?php

namespace Ajooda\AiMetering\Models;

use Ajooda\AiMetering\Database\Factories\AiCreditWalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiCreditWallet extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AiCreditWalletFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_credit_wallets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'billable_type',
        'billable_id',
        'balance',
        'currency',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:6',
        'meta' => 'array',
    ];

    /**
     * Get the billable entity
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the credit transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AiCreditTransaction::class, 'wallet_id');
    }

    /**
     * Check if the wallet has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Add credits to the wallet.
     */
    public function addCredits(float $amount, string $reason = 'top-up', array $meta = []): AiCreditTransaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'amount' => $amount,
            'direction' => 'credit',
            'reason' => $reason,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    /**
     * Deduct credits from the wallet.
     */
    public function deductCredits(float $amount, string $reason = 'usage', array $meta = []): AiCreditTransaction
    {
        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'amount' => $amount,
            'direction' => 'debit',
            'reason' => $reason,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
