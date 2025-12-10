<?php

namespace Ajooda\AiMetering\Models;

use Ajooda\AiMetering\Database\Factories\AiUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiUsage extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AiUsageFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_usages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'billable_type',
        'billable_id',
        'user_id',
        'tenant_id',
        'provider',
        'model',
        'feature',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'input_cost',
        'output_cost',
        'total_cost',
        'currency',
        'meta',
        'idempotency_key',
        'occurred_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'input_cost' => 'decimal:6',
        'output_cost' => 'decimal:6',
        'total_cost' => 'decimal:6',
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the billable entity
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to filter by billable entity.
     */
    public function scopeForBillable($query, $billable)
    {
        return $query->where('billable_type', get_class($billable))
            ->where('billable_id', $billable->id);
    }

    /**
     * Scope a query to filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query to filter by model.
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    /**
     * Scope a query to filter by feature.
     */
    public function scopeByFeature($query, string $feature)
    {
        return $query->where('feature', $feature);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeInPeriod($query, $start, $end = null)
    {
        $query->where('occurred_at', '>=', $start);

        if ($end) {
            $query->where('occurred_at', '<', $end);
        }

        return $query;
    }
}
