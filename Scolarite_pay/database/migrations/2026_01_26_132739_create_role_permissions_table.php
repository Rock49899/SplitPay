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
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignUuid('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();


            $table->index('role_id');
            $table->index('permission_id');
            
            // pour dire un rôle ne peut avoir la même permission qu'une seule fois
            $table->unique(['role_id', 'permission_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
