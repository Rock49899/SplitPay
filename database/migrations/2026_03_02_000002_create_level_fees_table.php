<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tarifs de scolarité par niveau + filière + année scolaire.
     *
     * Clé unique : (study_level_id, specialization_id, school_year)
     * specialization_id est nullable → tarif générique pour un niveau sans filière.
     *
     * Logique de résolution dans le code :
     *   1. Cherche (level, specialization, year)  → tarif spécifique filière
     *   2. Sinon  (level, NULL,           year)   → tarif générique du niveau
     */
    public function up(): void
    {
        Schema::create('level_fees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('study_level_id')
                  ->constrained('study_levels')
                  ->cascadeOnDelete();

            $table->foreignId('specialization_id')
                  ->nullable()
                  ->constrained('specializations')
                  ->nullOnDelete();

            $table->string('school_year', 20); // ex: "2025-2026"

            $table->decimal('tuition_amount', 12, 2)->default(0);

            $table->text('notes')->nullable(); // notes admin (optionnel)

            $table->timestamps();

            // Un seul tarif par (niveau, filière, année)
            $table->unique(['study_level_id', 'specialization_id', 'school_year'], 'uq_level_fees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_fees');
    }
};
