<?php

namespace Ajooda\AiMetering\Models;

use Ajooda\AiMetering\Database\Factories\AiPlanFactory;
use Ajooda\AiMetering\Support\ConditionalSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiPlan extends Model
{
    use ConditionalSoftDeletes, HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AiPlanFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'monthly_token_limit',
        'monthly_cost_limit',
        'overage_price_per_1k_tokens',
        'features',
        'is_active',
        'trial_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monthly_token_limit' => 'integer',
        'monthly_cost_limit' => 'decimal:6',
        'overage_price_per_1k_tokens' => 'decimal:6',
        'features' => 'array',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
    ];

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(AiSubscription::class, 'ai_plan_id');
    }

    /**
     * Check if the plan has unlimited tokens.
     */
    public function hasUnlimitedTokens(): bool
    {
        return $this->monthly_token_limit === null;
    }

    /**
     * Check if the plan has unlimited cost.
     */
    public function hasUnlimitedCost(): bool
    {
        return $this->monthly_cost_limit === null;
    }

    /**
     * Check if the plan allows overages.
     */
    public function allowsOverage(): bool
    {
        return $this->overage_price_per_1k_tokens !== null;
    }
}
