<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\StudyLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudyLevelController extends Controller
{
    use FiltersByAnnexe;

    public function index(Request $request)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $query = StudyLevel::query()
            ->where('annexe_id', $activeAnnexeId)
            ->with('annexe:id,name')
            ->orderBy('code');
        
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
            'code'        => [
                'required',
                'string',
                'max:50',
                Rule::unique('study_levels', 'code')->where(fn ($q) => $q->where('annexe_id', $activeAnnexeId)),
            ],
            'label'       => 'required|string|max:255',
            'order'       => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $studyLevel = StudyLevel::create(array_merge($validated, [
            'annexe_id' => $activeAnnexeId,
        ]));
        
        return response()->json(['study_level' => $studyLevel], 201);
    }

    public function show($id)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $studyLevel = StudyLevel::query()
            ->where('annexe_id', $activeAnnexeId)
            ->findOrFail($id);
        return response()->json(['study_level' => $studyLevel]);
    }

    public function update(Request $request, $id)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $studyLevel = StudyLevel::query()
            ->where('annexe_id', $activeAnnexeId)
            ->findOrFail($id);
        
        $validated = $request->validate([
            'code'        => [
                'required',
                'string',
                'max:50',
                Rule::unique('study_levels', 'code')
                    ->where(fn ($q) => $q->where('annexe_id', $studyLevel->annexe_id))
                    ->ignore($studyLevel->id),
            ],
            'label'       => 'required|string|max:255',
            'order'       => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $studyLevel->update($validated);
        
        return response()->json(['study_level' => $studyLevel]);
    }

    public function destroy($id)
    {
        $activeAnnexeId = $this->getActiveAnnexeId();

        if (!$activeAnnexeId) {
            return response()->json(['message' => 'Annexe active introuvable.'], 422);
        }

        $studyLevel = StudyLevel::query()
            ->where('annexe_id', $activeAnnexeId)
            ->findOrFail($id);
        $studyLevel->delete();
        
        return response()->json(['message' => 'Niveau supprimé avec succès']);
    }
}
