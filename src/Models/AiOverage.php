<?php

namespace Ajooda\AiMetering\Models;

use Ajooda\AiMetering\Database\Factories\AiOverageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiOverage extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AiOverageFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_overages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'billable_type',
        'billable_id',
        'ai_usage_id',
        'period_start',
        'period_end',
        'tokens',
        'cost',
        'currency',
        'stripe_invoice_item_id',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'tokens' => 'integer',
        'cost' => 'decimal:6',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the billable entity (User, Tenant, etc.).
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the usage record that triggered this overage.
     */
    public function usage(): BelongsTo
    {
        return $this->belongsTo(\Ajooda\AiMetering\Models\AiUsage::class, 'ai_usage_id');
    }

    /**
     * Check if this overage has been synced to Stripe.
     */
    public function isSynced(): bool
    {
        return $this->synced_at !== null;
    }

    /**
     * Mark this overage as synced.
     */
    public function markAsSynced(string $stripeInvoiceItemId): void
    {
        $this->update([
            'stripe_invoice_item_id' => $stripeInvoiceItemId,
            'synced_at' => now(),
        ]);
    }
}
