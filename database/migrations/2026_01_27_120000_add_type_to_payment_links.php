<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_links')) return;

        Schema::table('payment_links', function (Blueprint $table) {
            // ajouter le champ "type" (ex: 'tuition', 'other')
            if (! Schema::hasColumn('payment_links', 'type')) {
                $table->string('type', 64)->default('tuition')->after('student_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_links')) return;

        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
