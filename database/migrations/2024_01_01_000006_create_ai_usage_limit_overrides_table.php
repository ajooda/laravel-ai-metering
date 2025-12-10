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
        Schema::create('ai_usage_limit_overrides', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->timestamp('period_start')->index();
            $table->timestamp('period_end')->index();
            $table->unsignedBigInteger('token_limit')->nullable();
            $table->decimal('cost_limit', 12, 6)->nullable();
            $table->timestamps();

            // Composite index for period lookups
            $table->index(['billable_type', 'billable_id', 'period_start', 'period_end'], 'idx_billable_period_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_limit_overrides');
    }
};
