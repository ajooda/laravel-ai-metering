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
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->string('billable_type')->nullable();
            $table->unsignedBigInteger('billable_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tenant_id')->nullable();
            $table->string('provider', 50)->index();
            $table->string('model', 100)->index();
            $table->string('feature', 100)->nullable()->index();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('input_cost', 12, 6)->default(0);
            $table->decimal('output_cost', 12, 6)->default(0);
            $table->decimal('total_cost', 12, 6)->default(0);
            $table->string('currency', 3)->default('usd');
            $table->json('meta')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['billable_type', 'billable_id', 'occurred_at'], 'idx_billable_period');
            $table->index(['user_id', 'occurred_at'], 'idx_user_period');
            $table->index(['tenant_id', 'occurred_at'], 'idx_tenant_period');
            $table->index(['feature', 'occurred_at'], 'idx_feature_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
