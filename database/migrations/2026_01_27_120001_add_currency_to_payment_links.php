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
            if (! Schema::hasColumn('payment_links', 'currency')) {
                $table->string('currency', 10)->default('USD')->after('type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_links')) return;

        Schema::table('payment_links', function (Blueprint $table) {
            if (Schema::hasColumn('payment_links', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};
