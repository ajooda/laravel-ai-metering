<?php

namespace Ajooda\AiMetering\Models;

use Ajooda\AiMetering\Database\Factories\AiSubscriptionFactory;
use Ajooda\AiMetering\Support\ConditionalSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiSubscription extends Model
{
    use ConditionalSoftDeletes, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AiSubscriptionFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'billable_type',
        'billable_id',
        'ai_plan_id',
        'billing_mode',
        'renews_at',
        'started_at',
        'ends_at',
        'trial_ends_at',
        'grace_period_ends_at',
        'previous_plan_id',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'renews_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
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
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(AiPlan::class, 'ai_plan_id');
    }

    /**
     * Get the previous plan
     */
    public function previousPlan(): BelongsTo
    {
        return $this->belongsTo(AiPlan::class, 'previous_plan_id');
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        if ($this->ends_at === null) {
            return true;
        }

        if ($this->grace_period_ends_at && $this->grace_period_ends_at->isFuture()) {
            return true;
        }

        return $this->ends_at->isFuture();
    }

    /**
     * Check if the subscription is in trial.
     */
    public function isInTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is in grace period.
     */
    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (AiSubscription $subscription) {
            if ($subscription->billing_mode === 'credits' && $subscription->ai_plan_id === null) {
                throw new \InvalidArgumentException(
                    'Credits mode subscriptions must have a plan. A plan is required to define limits and subscription structure.'
                );
            }
        });
    }
}
