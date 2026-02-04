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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('installment_id')->constrained('installments')->onDelete('cascade');
            $table->string('reference', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('method', 50)->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('payplus_transaction_id', 150)->nullable();
            $table->string('payer_name', 150)->nullable();
            $table->string('payer_email', 150)->nullable();
            $table->string('payer_phone', 20)->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('installment_id');
            $table->index('reference');
            $table->index('status');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
