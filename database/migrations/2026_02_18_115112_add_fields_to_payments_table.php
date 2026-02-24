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
        Schema::table('payments', function (Blueprint $table) {
            // ajouter la colonne payment_link_id 
            $table->uuid('payment_link_id')->nullable()->after('id');
            $table->foreign('payment_link_id')
                  ->references('id')
                  ->on('payment_links')
                  ->onDelete('cascade');

            // ajouter la colonne metadata pour stocker JSON
            $table->json('metadata')->nullable()->after('method');

            // ajouter updated_at si tu veux le timestamp des mises à jour
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_link_id']);
            $table->dropColumn(['payment_link_id', 'metadata', 'updated_at']);
        });
    }
};
