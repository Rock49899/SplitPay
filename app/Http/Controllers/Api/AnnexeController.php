<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Annexe;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreAnnexeRequest;
use App\Http\Requests\UpdateAnnexeRequest;
use App\Models\LevelFee;
use App\Models\Specialization;
use App\Models\StudyLevel;
use Illuminate\Support\Facades\DB;

class AnnexeController extends Controller
{
    use FiltersByAnnexe;
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // GET /api/admin/annexes
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Annexe::query();

        // IMPORTANT: Filtrer uniquement les annexes de l'institution courante
        $query = $this->scopeByUserAnnexes($query, 'id');

        try {
            // normalize search (ignore empty strings)
            $raw = $request->get('search') ?? $request->get('q');
            $search = (is_string($raw) && strlen(trim($raw))) ? trim($raw) : null;

            // determine searchable columns that actually exist
            if ($search) {
                $searchable = ['name', 'code', 'email', 'phone', 'city'];
                $available = array_values(array_filter($searchable, function ($col) {
                    return Schema::hasColumn('annexes', $col);
                }));

                // relation existence checks
                $annexeModel = new Annexe();
                $hasManagerRel = method_exists($annexeModel, 'manager');
                $hasUserAnnexesRel = method_exists($annexeModel, 'user_annexes');

                if (count($available) || $hasManagerRel) {
                    $query->where(function ($q) use ($available, $search, $hasManagerRel) {
                        foreach ($available as $col) {
                            $q->orWhere($col, 'like', "%{$search}%");
                        }
                        if ($hasManagerRel) {
                            $q->orWhereHas('manager', function ($qa) use ($search) {
                                $qa->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                            });
                        }
                    });
                }
            }

            $with = [];
            $annexeModel = new Annexe();
            if (method_exists($annexeModel, 'manager')) $with[] = 'manager';
            if (method_exists($annexeModel, 'user_annexes')) {
                // nested relations are added only if pivot relation exists
                $with[] = 'user_annexes.role';
                $with[] = 'user_annexes.user';
            }
            if (count($with)) $query = $query->with($with);

            $annexes = $query->orderBy('name')->paginate($perPage);

            return response()->json($annexes, 200);
        } catch (QueryException $e) {
            \Log::error('AnnexeController@index query failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['message' => 'Failed to fetch annexes.'], 500);
        }
    }

    // POST /api/admin/annexes
    public function store(StoreAnnexeRequest $request)
    {
        $v = $request->validated();
        $platformAdmin = method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin();
        $institutionId = $platformAdmin
            ? ($v['institution_id'] ?? null)
            : $this->getCurrentInstitutionId();
        $sourceAnnexeId = $this->getUserPrincipalAnnexeId() ?: $this->getActiveAnnexeId();

        if (!$institutionId) {
            return response()->json(['message' => 'Institution introuvable pour cet utilisateur.'], 403);
        }

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'name' => $v['name'],
            'address' => $v['address'] ?? null,
            'city' => $v['city'] ?? null,
            'annexe_details' => $v['annexe_details'] ?? null,
            'is_active' => $v['is_active'] ?? true,
        ]);

        if ($sourceAnnexeId && $sourceAnnexeId !== $annexe->id) {
            try {
                $this->copyAcademicCatalog((string) $sourceAnnexeId, (string) $annexe->id);
            } catch (\Throwable $e) {
                \Log::warning('AnnexeController@store academic catalog copy failed', [
                    'source_annexe_id' => $sourceAnnexeId,
                    'target_annexe_id' => $annexe->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // renvoyer l'objet complet (created_at/updated_at inclus automatiquement)
        return response()->json(['message' => 'Annexe created', 'annexe' => $annexe], 201);
    }

    // GET /api/admin/annexes/{id}
    public function show($id)
    {
        $annexe = $this->scopeByUserAnnexes(Annexe::query(), 'id')->findOrFail($id);

        $with = [];
        if (method_exists($annexe, 'institution')) $with[] = 'institution';
        if (method_exists($annexe, 'users')) $with[] = 'users';
        if (method_exists($annexe, 'manager')) $with[] = 'manager';

        if (count($with)) {
            $annexe->load($with);
        }

        return response()->json($annexe, 200);
    }

    public function update(UpdateAnnexeRequest $request, $id)
    {
        try {
            $annexe = $this->scopeByUserAnnexes(Annexe::query(), 'id')->findOrFail($id);

            $data = $request->validated();
            // si client envoie 'annexe_details', mapper vers 'details'
            if (array_key_exists('annexe_details', $data)) {
                $data['annexe_details'] = $data['annexe_details'];
            }
            // mettre à jour uniquement les champs validés
            $annexe->update($data);

            // renvoyer l'objet complet après maj
            return response()->json(['message' => 'Annexe updated', 'annexe' => $annexe], 200);
        } catch (ValidationException $ve) {
            return response()->json(['message' => 'Validation failed', 'errors' => $ve->errors()], 422);
        } catch (\Throwable $e) {
            \Log::error('AnnexeController@update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
                'payload' => $request->all(),
            ]);
            // message générique pour le frontend
            return response()->json(['message' => 'Failed to update annexe (server error)'], 500);
        }
    }

    // DELETE /api/admin/annexes/{id}
    public function destroy($id)
    {
        $annexe = $this->scopeByUserAnnexes(Annexe::query(), 'id')->findOrFail($id);
        $annexe->delete();
        return response()->json(['message' => 'Annexe deleted'], 200);
    }

    private function copyAcademicCatalog(string $sourceAnnexeId, string $targetAnnexeId): void
    {
        DB::transaction(function () use ($sourceAnnexeId, $targetAnnexeId) {
            $studyLevels = StudyLevel::where('annexe_id', $sourceAnnexeId)
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            $specializations = Specialization::where('annexe_id', $sourceAnnexeId)
                ->orderBy('label')
                ->orderBy('id')
                ->get();

            if ($studyLevels->isEmpty() && $specializations->isEmpty()) {
                return;
            }

            $levelMap = [];
            foreach ($studyLevels as $level) {
                $newLevel = StudyLevel::create([
                    'annexe_id' => $targetAnnexeId,
                    'code' => $level->code,
                    'order' => $level->order,
                    'label' => $level->label,
                    'description' => $level->description,
                ]);

                $levelMap[$level->id] = $newLevel->id;
            }

            $specializationMap = [];
            foreach ($specializations as $specialization) {
                $newSpecialization = Specialization::create([
                    'annexe_id' => $targetAnnexeId,
                    'code' => $specialization->code,
                    'label' => $specialization->label,
                    'description' => $specialization->description,
                ]);

                $specializationMap[$specialization->id] = $newSpecialization->id;
            }

            $levelFees = LevelFee::where('annexe_id', $sourceAnnexeId)->get();
            foreach ($levelFees as $fee) {
                if (!isset($levelMap[$fee->study_level_id])) {
                    continue;
                }

                LevelFee::create([
                    'annexe_id' => $targetAnnexeId,
                    'study_level_id' => $levelMap[$fee->study_level_id],
                    'specialization_id' => $fee->specialization_id
                        ? ($specializationMap[$fee->specialization_id] ?? null)
                        : null,
                    'school_year' => $fee->school_year,
                    'tuition_amount' => $fee->tuition_amount,
                    'notes' => $fee->notes,
                ]);
            }
        });
    }
}
