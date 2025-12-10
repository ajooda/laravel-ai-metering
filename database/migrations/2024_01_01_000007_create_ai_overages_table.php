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
        Schema::create('ai_overages', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->foreignId('ai_usage_id')->nullable()->constrained('ai_usages')->nullOnDelete();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedInteger('tokens');
            $table->decimal('cost', 12, 6);
            $table->string('currency', 3)->default('usd');
            $table->string('stripe_invoice_item_id')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['billable_type', 'billable_id', 'synced_at'], 'idx_billable_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_overages');
    }
};
