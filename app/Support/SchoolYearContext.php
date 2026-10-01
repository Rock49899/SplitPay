<?php

namespace App\Support;

use App\Models\Annexe;
use App\Models\Student;
use Illuminate\Http\Request;

/**
 * Détermine l'institution dont le calendrier scolaire s'applique à la requête.
 */
class SchoolYearContext
{
    public static function institutionId(Request $request): ?string
    {
        if ($request->attributes->has('school_year_institution_id')) {
            return $request->attributes->get('school_year_institution_id');
        }

        $institutionId = null;

        // Espace étudiant : institution de l'annexe de l'étudiant
        $student = $request->attributes->get('student');
        if ($student instanceof Student) {
            $institutionId = $student->annexe?->institution_id;
        } elseif ($user = $request->user()) {
            // Espace admin : institution de l'annexe active, sinon celle de l'utilisateur
            $activeAnnexeId = $request->attributes->get('active_annexe_id');
            $institutionId = $activeAnnexeId
                ? Annexe::whereKey($activeAnnexeId)->value('institution_id')
                : null;

            $institutionId ??= $user->institutionId();
        }

        $request->attributes->set('school_year_institution_id', $institutionId);

        return $institutionId;
    }
}
