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
        Schema::create('study_levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // ex: L1, L2, L3, M1, M2
            $table->string('label'); // ex: Licence 1, Master 2
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_levels');
    }
};
