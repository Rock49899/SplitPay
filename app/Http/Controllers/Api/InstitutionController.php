<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Institution;

class InstitutionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    private function ensureInstitutionSuperAdmin(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('super_admin_institution')) {
            abort(403, 'Accès réservé au super administrateur institution.');
        }
    }

    // NOTE: in this SaaS instance the first registration creates the institution + default annexe + super-admin.
    // A full institutions CRUD is provided for administrative convenience but in typical single-tenant installs
    // only annexes will be managed after initial registration. Apply policies if you need to restrict create/destroy.

    // GET /api/admin/institutions
    public function index(Request $request)
    {
        $this->ensureInstitutionSuperAdmin();

        $perPage = (int) $request->get('per_page', 15);
        $query = Institution::query();

        if ($name = $request->get('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if (!is_null($request->get('is_active'))) {
            $query->where('is_active', (bool) $request->get('is_active'));
        }

        return response()->json($query->orderBy('name')->paginate($perPage), 200);
    }

    // POST /api/admin/institutions
    public function store(StoreInstitutionRequest $request)
    {
        $v = $request->validated();

        $institution = Institution::create(array_merge($v, [
            'id' => (string) Str::uuid(),
            'is_active' => $v['is_active'] ?? true,
        ]));

        return response()->json(['message' => 'Institution created', 'institution' => $institution], 201);
    }

    // GET /api/admin/institutions/{id}
    public function show($id)
    {
        $this->ensureInstitutionSuperAdmin();

        $institution = Institution::findOrFail($id);
        return response()->json($institution, 200);
    }

    // PUT/PATCH /api/admin/institutions/{id}
    public function update(UpdateInstitutionRequest $request, $id)
    {
        $this->ensureInstitutionSuperAdmin();

        $institution = Institution::findOrFail($id);
        $v = $request->validated();

        if ($request->boolean('remove_logo')) {
            if ($institution->logo) {
                Storage::disk('public')->delete($institution->logo);
            }
            $v['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($institution->logo) {
                Storage::disk('public')->delete($institution->logo);
            }
            $v['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $institution->update($v);

        return response()->json(['message' => 'Institution updated', 'institution' => $institution], 200);
    }

    // DELETE /api/admin/institutions/{id}
    public function destroy($id)
    {
        $institution = Institution::findOrFail($id);
        $institution->delete();
        return response()->json(['message' => 'Institution deleted'], 200);
    }

    // GET /api/admin/institutions/{id}/annexes
    public function annexes($id)
    {
        $this->ensureInstitutionSuperAdmin();

        $institution = Institution::with('annexes')->findOrFail($id);
        return response()->json($institution->annexes, 200);
    }
}
