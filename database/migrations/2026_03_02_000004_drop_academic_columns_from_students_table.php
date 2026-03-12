<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retirer de la table students les colonnes désormais gérées par `enrollments`.
     *
     * Ce qui part → où aller
     * ──────────────────────────────────────────────────────────────
     * school_year      → enrollment.school_year
     * tuition_amount   → enrollment.tuition_amount  (snapshot)
     * amount_paid      → enrollment.amount_paid
     * study_level_id   → enrollment → level_fee → study_level_id
     * class (string)   → enrollment → level_fee ou colonne class_id
     * class_id         → à gérer dans enrollment si besoin
     *
     * Ce qui reste sur students
     * ──────────────────────────────────────────────────────────────
     * annexe_id, matricule, first_name, last_name, email, phone,
     * avatar, specialization_id (filière semi-permanente), status
     */
    public function up(): void
    {
        // Supprimer d'abord les FK qui existent (sans crasher si absentes)
        foreach (['students_study_level_id_foreign'] as $fk) {
            try {
                Schema::table('students', fn (Blueprint $t) => $t->dropForeign($fk));
            } catch (\Throwable) {
                // FK absente — on passe
            }
        }

        // Supprimer uniquement les colonnes présentes
        $toDrop = array_filter(
            ['school_year', 'tuition_amount', 'amount_paid', 'study_level_id'],
            fn (string $col) => Schema::hasColumn('students', $col)
        );

        if (!empty($toDrop)) {
            Schema::table('students', fn (Blueprint $t) => $t->dropColumn(array_values($toDrop)));
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('school_year', 20)->after('phone')->default('');
            $table->decimal('tuition_amount', 10, 2)->after('school_year')->default(0);
            $table->decimal('amount_paid', 10, 2)->after('tuition_amount')->default(0);
            $table->foreignId('study_level_id')->nullable()->after('amount_paid')->constrained('study_levels')->nullOnDelete();
        });
    }
};
