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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('annexe_id')->nullable()->constrained('annexes')->onDelete('set null');
            $table->string('matricule', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150);
            $table->string('phone', 20)->nullable();
            $table->string('class', 100)->nullable();
            $table->string('school_year', 20);
            $table->decimal('tuition_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('status', ['active', 'suspended', 'graduated'])->default('active');
            $table->timestamps();
            
            $table->index('annexe_id');
            $table->index('email');
            $table->index('school_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
