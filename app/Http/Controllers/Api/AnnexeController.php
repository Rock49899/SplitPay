<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Annexe;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreAnnexeRequest;
use App\Http\Requests\UpdateAnnexeRequest;

class AnnexeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // GET /api/admin/annexes
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Annexe::query();

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
        $institutionId = auth()->user()->annexe?->institution_id;

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'name' => $v['name'],
            'address' => $v['address'] ?? null,
            'city' => $v['city'] ?? null,
            'annexe_details' => $v['annexe_details'] ?? null,
            'is_active' => $v['is_active'] ?? true,
        ]);

        // renvoyer l'objet complet (created_at/updated_at inclus automatiquement)
        return response()->json(['message' => 'Annexe created', 'annexe' => $annexe], 201);
    }

    // GET /api/admin/annexes/{id}
    public function show($id)
    {
        // include manager & pivot info
        $annexe = Annexe::with(['user_annexes.role', 'user_annexes.user'])->findOrFail($id);
        return response()->json($annexe, 200);
    }

    public function update(UpdateAnnexeRequest $request, $id)
    {
        try {
            $annexe = Annexe::findOrFail($id);

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
        $annexe = Annexe::findOrFail($id);
        $annexe->delete();
        return response()->json(['message' => 'Annexe deleted'], 200);
    }
}
