<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rendre installment_id nullable sur payments.
     * Le flux publicCheckout crée des paiements sans passer par un Installment.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['installment_id']);
            $table->uuid('installment_id')->nullable()->change();
            $table->foreign('installment_id')
                  ->references('id')
                  ->on('installments')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['installment_id']);
            $table->uuid('installment_id')->nullable(false)->change();
            $table->foreign('installment_id')
                  ->references('id')
                  ->on('installments')
                  ->onDelete('cascade');
        });
    }
};
