<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_levels', function (Blueprint $table) {
            // Détermine l'ordre de progression : L1=1, L2=2, M1=3, M2=4 …
            $table->unsignedSmallInteger('order')->default(0)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('study_levels', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
