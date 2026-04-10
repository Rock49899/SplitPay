<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\LevelFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LevelFeeController extends Controller
{
    use FiltersByAnnexe;

    /**
     * Liste tous les tarifs (filtrable par niveau / filière / année).
     * GET admin/level-fees
     */
    public function index(Request $request): JsonResponse
    {
        $fees = $this->scopeByUserAnnexes(LevelFee::query())
            ->with('studyLevel', 'specialization')
            ->when($request->school_year,       fn($q) => $q->where('school_year',        $request->school_year))
            ->when($request->study_level_id,    fn($q) => $q->where('study_level_id',    $request->study_level_id))
            ->when($request->specialization_id, fn($q) => $q->where('specialization_id', $request->specialization_id))
            ->orderBy('school_year', 'desc')
            ->get();

        return response()->json(['data' => $fees]);
    }

    /**
     * Crée un nouveau tarif.
     * POST admin/level-fees
     */
    public function store(Request $request): JsonResponse
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $data = $request->validate([
            'study_level_id'    => [
                'required',
                Rule::exists('study_levels', 'id')->where(fn ($q) => $q->where('annexe_id', $activeAnnexeId)),
            ],
            'specialization_id' => [
                'nullable',
                Rule::exists('specializations', 'id')->where(fn ($q) => $q->where('annexe_id', $activeAnnexeId)),
            ],
            'school_year'       => 'required|string|max:20',
            'tuition_amount'    => 'required|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        $data['annexe_id'] = $activeAnnexeId;

        $fee = LevelFee::create($data);

        return response()->json(['data' => $fee->load('studyLevel', 'specialization')], 201);
    }

    /**
     * Modifie un tarif existant.
     * PUT admin/level-fees/{id}
     */
    public function update(Request $request, LevelFee $levelFee): JsonResponse
    {
        if (!$this->userHasAccessToAnnexe((string) $levelFee->annexe_id)) {
            abort(404);
        }

        $data = $request->validate([
            'tuition_amount' => 'sometimes|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $levelFee->update($data);

        return response()->json(['data' => $levelFee->fresh('studyLevel', 'specialization')]);
    }

    /**
     * Supprime un tarif (bloqué si des enrollments l'utilisent).
     * DELETE admin/level-fees/{id}
     */
    public function destroy(LevelFee $levelFee): JsonResponse
    {
        if (!$this->userHasAccessToAnnexe((string) $levelFee->annexe_id)) {
            abort(404);
        }

        if ($levelFee->enrollments()->exists()) {
            return response()->json([
                'message' => 'Ce tarif est utilisé par des inscriptions existantes et ne peut pas être supprimé.',
            ], 422);
        }

        $levelFee->delete();

        return response()->json(null, 204);
    }

    /**
     * Copie tous les barèmes d'une année scolaire vers une autre.
     * Les barèmes déjà existants pour la cible sont ignorés (pas d'écrasement).
     * POST admin/level-fees/copy-year
     */
    public function copyYear(Request $request): JsonResponse
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $data = $request->validate([
            'from_year' => 'required|string|max:20',
            'to_year'   => 'required|string|max:20|different:from_year',
        ]);

        $source = LevelFee::where('annexe_id', $activeAnnexeId)
            ->where('school_year', $data['from_year'])
            ->get();

        if ($source->isEmpty()) {
            return response()->json([
                'message' => "Aucun barème configuré pour l'année {$data['from_year']}.",
            ], 422);
        }

        $created = 0;
        $skipped = 0;

        foreach ($source as $fee) {
            $exists = LevelFee::where('annexe_id',          $activeAnnexeId)
                              ->where('study_level_id',    $fee->study_level_id)
                              ->where('school_year',        $data['to_year'])
                              ->where('specialization_id', $fee->specialization_id)
                              ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            LevelFee::create([
                'annexe_id'         => $activeAnnexeId,
                'study_level_id'    => $fee->study_level_id,
                'specialization_id' => $fee->specialization_id,
                'school_year'       => $data['to_year'],
                'tuition_amount'    => $fee->tuition_amount,
                'notes'             => $fee->notes,
            ]);
            $created++;
        }

        return response()->json([
            'message' => "{$created} barème(s) copié(s) de {$data['from_year']} vers {$data['to_year']}.",
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Résout le montant de scolarité pour un niveau + filière + année donnés.
     * Utilisé par le formulaire de création étudiant pour auto-remplir le champ.
     *
     * GET admin/level-fees/resolve?study_level_id=1&specialization_id=2&school_year=2025-2026
     */
    public function resolve(Request $request): JsonResponse
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $request->validate([
            'study_level_id'    => [
                'required',
                Rule::exists('study_levels', 'id')->where(fn ($q) => $q->where('annexe_id', $activeAnnexeId)),
            ],
            'specialization_id' => [
                'nullable',
                Rule::exists('specializations', 'id')->where(fn ($q) => $q->where('annexe_id', $activeAnnexeId)),
            ],
            'school_year'       => 'required|string',
        ]);

        $fee = LevelFee::resolve(
            (int) $request->study_level_id,
            $request->specialization_id ? (int) $request->specialization_id : null,
            $request->school_year,
            $activeAnnexeId
        );

        if (!$fee) {
            return response()->json([
                'data'    => null,
                'message' => 'Aucun tarif configuré pour cette combinaison niveau / filière / année.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'             => $fee->id,
                'tuition_amount' => $fee->tuition_amount,
                'study_level'    => $fee->studyLevel?->label,
                'specialization' => $fee->specialization?->label,
                'school_year'    => $fee->school_year,
                'matched_on'     => $fee->specialization_id ? 'specific' : 'generic',
            ],
        ]);
    }
}
