<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique d'inscription d'un étudiant : 1 ligne par étudiant par année scolaire.
     *
     * - level_fee_id  → le barème utilisé (niveau + filière + année)
     * - tuition_amount → snapshot du montant au moment de l'inscription (immuable)
     * - amount_paid   → total payé sur cet enrollment (mis à jour à chaque paiement)
     * - status        → active | completed (tout payé) | abandoned
     * - promoted_at   → date de passage en année supérieure (null = pas encore promu)
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            $table->foreignId('level_fee_id')
                  ->constrained('level_fees')
                  ->restrictOnDelete(); // ne pas supprimer un tarif utilisé

            // Snapshot du montant au moment de l'inscription
            // (si le tarif change l'année suivante l'historique reste correct)
            $table->decimal('tuition_amount', 12, 2);

            // Montant cumulé payé sur cet enrollment
            $table->decimal('amount_paid', 12, 2)->default(0);

            $table->string('school_year', 20); // redondant avec level_fee mais pratique pour requêtes

            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');

            $table->timestamp('promoted_at')->nullable(); // date de passage au niveau suivant

            $table->text('notes')->nullable();

            $table->timestamps();

            // Un seul enrollment actif par étudiant par année
            $table->unique(['student_id', 'school_year'], 'uq_enrollment_student_year');

            $table->index('level_fee_id');
            $table->index('school_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
