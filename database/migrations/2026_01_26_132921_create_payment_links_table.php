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
        Schema::create('payment_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->string('token', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('description', 255)->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['active', 'used', 'expired'])->default('active');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('student_id');
            $table->index('token');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
