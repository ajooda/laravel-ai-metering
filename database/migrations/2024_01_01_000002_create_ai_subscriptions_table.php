<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->foreignId('ai_plan_id')->nullable()->constrained('ai_plans')->nullOnDelete();
            $table->enum('billing_mode', ['plan', 'credits'])->default('plan');
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->foreignId('previous_plan_id')->nullable()->constrained('ai_plans')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // One active subscription per billable
            $table->index(['billable_type', 'billable_id', 'ends_at'], 'idx_billable_active');
            $table->index('ai_plan_id');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_subscriptions');
    }
};
