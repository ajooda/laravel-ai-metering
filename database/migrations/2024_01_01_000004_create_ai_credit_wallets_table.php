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
        Schema::create('ai_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->decimal('balance', 12, 6)->default(0);
            $table->string('currency', 3)->default('usd');
            $table->json('meta')->nullable();
            $table->timestamps();

            // One wallet per billable
            $table->unique(['billable_type', 'billable_id'], 'idx_billable_wallet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_credit_wallets');
    }
};
