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
        Schema::create('user_annexes', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('annexe_id')->constrained('annexes')->onDelete('cascade');
            $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
            $table->boolean('is_principal')->default(false);
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->useCurrent();
            $table->datetime('end_at')->nullable();

            // Clé primaire composite
            $table->primary(['user_id', 'annexe_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_annexes');
    }
};
