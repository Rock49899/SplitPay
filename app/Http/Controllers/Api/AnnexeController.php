<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Annexe;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

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

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $v['institution_id'],
            'name' => $v['name'],
            'is_active' => $v['is_active'] ?? true,
        ]);

        return response()->json(['message' => 'Annexe created', 'annexe' => $annexe], 201);
    }

    // GET /api/admin/annexes/{id}
    public function show($id)
    {
        // include manager & pivot info
        $annexe = Annexe::with(['user_annexes.role', 'user_annexes.user'])->findOrFail($id);
        return response()->json($annexe, 200);
    }

    // PUT/PATCH /api/admin/annexes/{id}
    public function update(UpdateAnnexeRequest $request, $id)
    {
        $annexe = Annexe::findOrFail($id);
        $v = $request->validated();
        $annexe->update($v);

        return response()->json(['message' => 'Annexe updated', 'annexe' => $annexe], 200);
    }

    // DELETE /api/admin/annexes/{id}
    public function destroy($id)
    {
        $annexe = Annexe::findOrFail($id);
        $annexe->delete();
        return response()->json(['message' => 'Annexe deleted'], 200);
    }
}
