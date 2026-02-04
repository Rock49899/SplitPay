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
        Schema::create('installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_link_id')->constrained('payment_links')->onDelete('cascade');
            $table->integer('tranche_number');
            $table->string('description', 100)->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('payment_link_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
