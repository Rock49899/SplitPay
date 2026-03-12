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
        Schema::table('installments', function (Blueprint $table) {
            $table->timestamp('last_reminder_sent_at')->nullable()->after('created_at');
            $table->integer('reminder_count')->default(0)->after('last_reminder_sent_at');
            
            // Index pour queries rapides
            $table->index(['due_date', 'reminder_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['due_date', 'reminder_count']);
            $table->dropColumn(['last_reminder_sent_at', 'reminder_count']);
        });
    }
};
