<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\Enrollment;
use App\Models\LevelFee;
use App\Models\SchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    use FiltersByAnnexe;

    /**
     * Retourne la liste des années scolaires disponibles.
     * Fusionne les années présentes en DB avec une plage générée autour de l'année courante.
     * GET admin/school-years
     */
    public function schoolYears(): JsonResponse
    {
        $years = SchoolYear::query()
            ->whereIn('status', ['active', 'closed'])
            ->orderByDesc('year')
            ->pluck('year')
            ->values()
            ->all();

        return response()->json(['years' => $years]);
    }

    /**
     * Retourne le contexte d'année réellement appliqué par le middleware.
     * GET admin/school-years/context
     * GET student/school-year/context
     */
    public function context(Request $request): JsonResponse
    {
        $years = SchoolYear::query()
            ->whereIn('status', ['active', 'closed'])
            ->orderByDesc('year')
            ->pluck('year')
            ->values()
            ->all();

        $requested = $request->attributes->get('requested_school_year')
            ?: ($request->query('school_year') ?: $request->input('school_year'));

        $effective = $request->attributes->get('effective_school_year')
            ?: ($request->input('school_year') ?: $request->attributes->get('active_school_year'));

        $status = null;
        if (!empty($effective)) {
            $status = SchoolYear::where('year', $effective)->value('status');
        }

        return response()->json([
            'requested_year' => $requested,
            'effective_year' => $effective,
            'status' => $status,
            'available' => (bool) $request->attributes->get('school_year_available', true),
            'read_only' => (bool) $request->attributes->get('school_year_read_only', false),
            'years' => $years,
        ]);
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
        $allowedAnnexeIds = array_values(array_filter($this->getAccessibleAnnexeIds()));

        if (empty($allowedAnnexeIds)) {
            return response()->json([
                'from_year' => $fromYear,
                'to_year' => $toYear,
                'summary' => [
                    'total' => 0,
                    'promotable' => 0,
                    'terminal' => 0,
                    'no_level' => 0,
                ],
                'details' => [],
            ]);
        }

        $enrollments = Enrollment::active()
            ->forYear($fromYear)
            ->whereHas('student', fn ($q) => $q->whereIn('annexe_id', $allowedAnnexeIds))
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
                ? LevelFee::resolve($nextLevel->id, $e->student->specialization_id, $toYear, (string) $e->student->annexe_id)
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
        $allowedAnnexeIds = array_values(array_filter($this->getAccessibleAnnexeIds()));

        if (empty($allowedAnnexeIds)) {
            return response()->json([
                'message' => 'Aucune annexe accessible pour cette opération.',
            ], 403);
        }

        $fromSchoolYear = SchoolYear::firstOrCreate(
            ['year' => $fromYear],
            ['status' => 'active', 'opened_at' => now()]
        );

        if ($fromSchoolYear->status !== 'active') {
            return response()->json([
                'message' => "L'année $fromYear n'est pas active et ne peut pas être clôturée.",
            ], 422);
        }

        $toSchoolYear = SchoolYear::firstOrCreate(
            ['year' => $toYear],
            ['status' => 'draft']
        );

        // Vérification : l'année source doit avoir des inscriptions actives
        $count = Enrollment::active()
            ->forYear($fromYear)
            ->whereHas('student', fn ($q) => $q->whereIn('annexe_id', $allowedAnnexeIds))
            ->count();
        if ($count === 0) {
            return response()->json([
                'message' => "Aucune inscription active pour l'année $fromYear.",
            ], 422);
        }

        $query = Enrollment::active()
            ->forYear($fromYear)
            ->whereHas('student', fn ($q) => $q->whereIn('annexe_id', $allowedAnnexeIds))
            ->with('student.specialization', 'levelFee.studyLevel');

        if ($filterIds) {
            $query->whereIn('student_id', $filterIds);
        }

        $enrollments = $query->get();

        $promoted  = 0;
        $graduated = 0;
        $skipped   = 0;
        $errors    = [];

        DB::transaction(function () use ($enrollments, $toYear, $fromSchoolYear, $toSchoolYear, &$promoted, &$graduated, &$skipped, &$errors) {
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
                        $toYear,
                        (string) $enrollment->student->annexe_id
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

            // Cycle de vie des années scolaires
            SchoolYear::where('status', 'active')
                ->where('year', '!=', $toSchoolYear->year)
                ->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

            $fromSchoolYear->update([
                'status' => 'closed',
                'closed_at' => now(),
                'promoted_to_year' => $toSchoolYear->year,
            ]);

            $toSchoolYear->update([
                'status' => 'active',
                'opened_at' => $toSchoolYear->opened_at ?: now(),
                'closed_at' => null,
            ]);
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
