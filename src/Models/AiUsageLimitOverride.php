<?php

namespace Ajooda\AiMetering\Models;

use Ajooda\AiMetering\Database\Factories\AiUsageLimitOverrideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiUsageLimitOverride extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AiUsageLimitOverrideFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_usage_limit_overrides';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'billable_type',
        'billable_id',
        'period_start',
        'period_end',
        'token_limit',
        'cost_limit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'token_limit' => 'integer',
        'cost_limit' => 'decimal:6',
    ];

    /**
     * Get the billable entity (User, Tenant, etc.).
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if this override applies to the given date.
     */
    public function appliesTo(\DateTimeInterface $date): bool
    {
        return $date >= $this->period_start && $date < $this->period_end;
    }
}
