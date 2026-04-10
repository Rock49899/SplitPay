<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecializationController extends Controller
{
    use FiltersByAnnexe;

    public function index(Request $request)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $query = Specialization::query()
            ->where('annexe_id', $activeAnnexeId)
            ->orderBy('label');
        
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%");
            });
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function store(Request $request)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('specializations', 'code')->where(fn ($q) => $q->where('annexe_id', $activeAnnexeId)),
            ],
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [], [
            'code' => 'sigle',
            'label' => 'libellé',
            'description' => 'description',
        ]);

        $specialization = Specialization::create(array_merge($validated, [
            'annexe_id' => $activeAnnexeId,
        ]));
        
        return response()->json(['specialization' => $specialization], 201);
    }

    public function show($id)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $specialization = Specialization::query()
            ->where('annexe_id', $activeAnnexeId)
            ->findOrFail($id);
        return response()->json(['specialization' => $specialization]);
    }

    public function update(Request $request, $id)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $specialization = Specialization::query()
            ->where('annexe_id', $activeAnnexeId)
            ->findOrFail($id);
        
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('specializations', 'code')
                    ->where(fn ($q) => $q->where('annexe_id', $specialization->annexe_id))
                    ->ignore($specialization->id),
            ],
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [], [
            'code' => 'sigle',
            'label' => 'libellé',
            'description' => 'description',
        ]);

        $specialization->update($validated);
        
        return response()->json(['specialization' => $specialization]);
    }

    public function destroy($id)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $specialization = Specialization::query()
            ->where('annexe_id', $activeAnnexeId)
            ->findOrFail($id);
        $specialization->delete();
        
        return response()->json(['message' => 'Spécialisation supprimée avec succès']);
    }
}
