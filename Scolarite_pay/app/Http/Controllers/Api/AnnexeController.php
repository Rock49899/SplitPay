<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Annexe;

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

        if ($institutionId = $request->get('institution_id')) {
            $query->where('institution_id', $institutionId);
        }

        if ($name = $request->get('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        return response()->json($query->orderBy('name')->paginate($perPage), 200);
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
        $annexe = Annexe::findOrFail($id);
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
