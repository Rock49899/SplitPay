<?php

namespace App\Observers;

use App\Models\Student;
use App\Models\LevelFee;
use App\Models\Enrollment;

class StudentObserver
{
    /**
     * Avant la création : résoudre automatiquement le tuition_amount
     * depuis level_fees si study_level_id est renseigné.
     *
     * L'admin n'a qu'à sélectionner le niveau + la filière : le montant
     * est rempli automatiquement depuis le barème.
     */
    public function creating(Student $student): void
    {
        $this->resolveTuition($student);
    }

    /**
     * Après la création : créer l'enrollment de l'année courante.
     */
    public function created(Student $student): void
    {
        $this->syncEnrollment($student);
    }

    /**
     * Avant la mise à jour : si le niveau / la filière / l'année changent,
     * recalculer le tuition_amount depuis le barème.
     */
    public function updating(Student $student): void
    {
        $changed = $student->isDirty(['study_level_id', 'specialization_id', 'school_year']);
        if ($changed) {
            $this->resolveTuition($student);
        }
    }

    /**
     * Après la mise à jour : si l'année scolaire / le niveau / la filière
     * ont changé, créer ou mettre à jour l'enrollment correspondant.
     */
    public function updated(Student $student): void
    {
        $changed = $student->wasChanged(['study_level_id', 'specialization_id', 'school_year']);
        if ($changed) {
            $this->syncEnrollment($student);
        }
    }

    // ── Helpers privés ─────────────────────────────────────────────────────────

    /**
     * Résoudre le tuition_amount depuis level_fees.
     * Modifie $student->tuition_amount si un tarif est trouvé.
     */
    private function resolveTuition(Student $student): void
    {
        if (!$student->study_level_id || !$student->school_year) {
            return; // pas assez d'infos → laisser la valeur saisie
        }

        $fee = LevelFee::resolve(
            (int) $student->study_level_id,
            $student->specialization_id ? (int) $student->specialization_id : null,
            $student->school_year
        );

        if ($fee) {
            $student->tuition_amount = $fee->tuition_amount;
        }
    }

    /**
     * Créer (ou mettre à jour) l'enrollment pour l'année scolaire courante.
     * Si un enrollment existe déjà pour cet étudiant + cette année, on met
     * juste à jour le level_fee_id et le snapshot du tuition_amount.
     */
    private function syncEnrollment(Student $student): void
    {
        if (!$student->study_level_id || !$student->school_year) {
            return;
        }

        $fee = LevelFee::resolve(
            (int) $student->study_level_id,
            $student->specialization_id ? (int) $student->specialization_id : null,
            $student->school_year
        );

        if (!$fee) {
            return; // aucun barème configuré → pas d'enrollment automatique
        }

        Enrollment::updateOrCreate(
            [
                'student_id'  => $student->id,
                'school_year' => $student->school_year,
            ],
            [
                'level_fee_id'   => $fee->id,
                'tuition_amount' => $fee->tuition_amount,
                // amount_paid n'est PAS écrasé si l'enrollment existe déjà
            ]
        );
    }
}
