<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\LevelFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{

    /**
     * Retourne la liste des années scolaires disponibles.
     * Fusionne les années présentes en DB avec une plage générée autour de l'année courante.
     * GET admin/school-years
     */
    public function schoolYears(): JsonResponse
    {
        $dbYears = Enrollment::distinct()->orderBy('school_year', 'desc')->pluck('school_year')->toArray();

        $now  = now();
        $base = $now->month >= 9 ? $now->year : $now->year - 1;

        $generated = [];
        for ($i = -1; $i <= 4; $i++) {
            $y           = $base - $i;
            $generated[] = "$y-" . ($y + 1);
        }

        $years = array_values(array_unique(array_merge($generated, $dbYears)));
        usort($years, fn ($a, $b) => strcmp($b, $a)); // décroissant

        return response()->json(['years' => $years]);
    }

    /**
     * Prévisualise la promotion d'une année scolaire.
     * GET admin/promotions/preview?from_year=2024-2025&to_year=2025-2026
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'from_year' => 'required|string',
            'to_year'   => 'required|string',
        ]);

        $fromYear = $request->from_year;
        $toYear   = $request->to_year;

        $enrollments = Enrollment::active()
            ->forYear($fromYear)
            ->with('student.specialization', 'levelFee.studyLevel')
            ->get();

        $total      = $enrollments->count();
        $promotable = 0;
        $terminal   = 0; // dernier niveau → diplômés
        $noLevel    = 0; // inscription sans niveau configuré

        $details = $enrollments->map(function (Enrollment $e) use ($toYear, &$promotable, &$terminal, &$noLevel) {
            $studyLevel = $e->levelFee?->studyLevel;
            $nextLevel  = $studyLevel?->nextLevel();
            $fee        = $nextLevel
                ? LevelFee::resolve($nextLevel->id, $e->student->specialization_id, $toYear)
                : null;

            if (!$studyLevel) {
                $noLevel++;
                $outcome = 'no_level';
            } elseif (!$nextLevel) {
                $terminal++;
                $outcome = 'terminal'; // sera marqué diplômé
            } else {
                $promotable++;
                $outcome = 'promote';
            }

            return [
                'student_id'         => $e->student_id,
                'student_name'       => $e->student->first_name . ' ' . $e->student->last_name,
                'matricule'          => $e->student->matricule,
                'current_level'      => $studyLevel?->label ?? '—',
                'next_level'         => $nextLevel?->label ?? null,
                'specialization'     => $e->student->specialization?->label ?? null,
                'resolved_fee'       => $fee?->tuition_amount ?? null,
                'fee_missing'        => $nextLevel && !$fee,
                'outcome'            => $outcome, // promote | terminal | no_level
            ];
        });

        return response()->json([
            'from_year'  => $fromYear,
            'to_year'    => $toYear,
            'summary'    => [
                'total'      => $total,
                'promotable' => $promotable,
                'terminal'   => $terminal,   // diplômés
                'no_level'   => $noLevel,
            ],
            'details' => $details,
        ]);
    }

    /**
     * Exécute la promotion d'une année scolaire.
     * POST admin/promotions
     *
     * Chaque étudiant ayant une inscription active sur from_year :
     *  - obtient une nouvelle inscription sur to_year au niveau N+1
     *  - son inscription précédente passe en "completed"
     *  - si dernier niveau → inscription précédente passe en "completed", pas de nouvelle inscription
     */
    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_year'    => 'required|string',
            'to_year'      => 'required|string',
            'student_ids'  => 'nullable|array',
            'student_ids.*'=> 'uuid|exists:students,id',
        ]);

        $fromYear   = $validated['from_year'];
        $toYear     = $validated['to_year'];
        $filterIds  = $validated['student_ids'] ?? null;

        // Vérification : l'année source doit avoir des inscriptions actives
        $count = Enrollment::active()->forYear($fromYear)->count();
        if ($count === 0) {
            return response()->json([
                'message' => "Aucune inscription active pour l'année $fromYear.",
            ], 422);
        }

        $query = Enrollment::active()
            ->forYear($fromYear)
            ->with('student.specialization', 'levelFee.studyLevel');

        if ($filterIds) {
            $query->whereIn('student_id', $filterIds);
        }

        $enrollments = $query->get();

        $promoted  = 0;
        $graduated = 0;
        $skipped   = 0;
        $errors    = [];

        DB::transaction(function () use ($enrollments, $toYear, &$promoted, &$graduated, &$skipped, &$errors) {
            foreach ($enrollments as $enrollment) {
                try {
                    $studyLevel = $enrollment->levelFee?->studyLevel;
                    $nextLevel  = $studyLevel?->nextLevel();

                    // Clôturer l'inscription courante dans tous les cas
                    $enrollment->update([
                        'status'       => 'completed',
                        'promoted_at'  => now(),
                    ]);

                    if (!$studyLevel) {
                        // Pas de niveau configuré → on clôture sans promouvoir
                        $skipped++;
                        continue;
                    }

                    if (!$nextLevel) {
                        // Dernier niveau → diplômé
                        $graduated++;
                        continue;
                    }

                    // Résoudre le tarif pour le prochain niveau
                    $fee = LevelFee::resolve(
                        $nextLevel->id,
                        $enrollment->student->specialization_id,
                        $toYear
                    );

                    // Créer la nouvelle inscription (évite les doublons)
                    Enrollment::firstOrCreate(
                        [
                            'student_id'  => $enrollment->student_id,
                            'school_year' => $toYear,
                        ],
                        [
                            'level_fee_id'   => $fee?->id,
                            'tuition_amount' => $fee?->tuition_amount ?? 0,
                            'amount_paid'    => 0,
                            'status'         => 'active',
                        ]
                    );

                    $promoted++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'student_id' => $enrollment->student_id,
                        'error'      => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'message'   => "Clôture de l'année $fromYear exécutée.",
            'from_year' => $fromYear,
            'to_year'   => $toYear,
            'result'    => [
                'promoted'  => $promoted,
                'graduated' => $graduated,
                'skipped'   => $skipped,
                'errors'    => $errors,
            ],
        ]);
    }
}
