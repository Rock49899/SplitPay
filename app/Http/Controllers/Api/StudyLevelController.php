<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyLevel;
use Illuminate\Http\Request;

class StudyLevelController extends Controller
{
    public function index(Request $request)
    {
        $query = StudyLevel::query()->orderBy('code');
        
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
        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:study_levels,code',
            'label'       => 'required|string|max:255',
            'order'       => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $studyLevel = StudyLevel::create($validated);
        
        return response()->json(['study_level' => $studyLevel], 201);
    }

    public function show($id)
    {
        $studyLevel = StudyLevel::findOrFail($id);
        return response()->json(['study_level' => $studyLevel]);
    }

    public function update(Request $request, $id)
    {
        $studyLevel = StudyLevel::findOrFail($id);
        
        $validated = $request->validate([
            'code'        => 'required|string|max:50|unique:study_levels,code,' . $id,
            'label'       => 'required|string|max:255',
            'order'       => 'nullable|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $studyLevel->update($validated);
        
        return response()->json(['study_level' => $studyLevel]);
    }

    public function destroy($id)
    {
        $studyLevel = StudyLevel::findOrFail($id);
        $studyLevel->delete();
        
        return response()->json(['message' => 'Niveau supprimé avec succès']);
    }
}
