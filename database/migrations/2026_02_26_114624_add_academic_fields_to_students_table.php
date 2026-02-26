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
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('study_level_id')->nullable()->after('class')->constrained('study_levels')->nullOnDelete();
            $table->foreignId('specialization_id')->nullable()->after('study_level_id')->constrained('specializations')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->after('specialization_id')->constrained('classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['study_level_id']);
            $table->dropForeign(['specialization_id']);
            $table->dropForeign(['class_id']);
            $table->dropColumn(['study_level_id', 'specialization_id', 'class_id']);
        });
    }
};
