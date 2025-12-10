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
        Schema::create('ai_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('ai_credit_wallets')->cascadeOnDelete();
            $table->decimal('amount', 12, 6);
            $table->enum('direction', ['credit', 'debit']);
            $table->string('reason', 255);
            $table->json('meta')->nullable();
            $table->timestamp('created_at');

            $table->index(['wallet_id', 'created_at'], 'idx_wallet_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_credit_transactions');
    }
};
