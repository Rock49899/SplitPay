<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentClass;
use Illuminate\Http\Request;

class StudentClassController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentClass::with(['studyLevel', 'specialization'])->orderBy('label');
        
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('label', 'like', "%{$s}%");
            });
        }
        
        if ($request->filled('study_level_id')) {
            $query->where('study_level_id', $request->study_level_id);
        }
        
        if ($request->filled('specialization_id')) {
            $query->where('specialization_id', $request->specialization_id);
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'study_level_id' => 'required|exists:study_levels,id',
            'specialization_id' => 'required|exists:specializations,id',
            'code' => 'required|string|max:50',
            'label' => 'required|string|max:255',
        ]);

        $studentClass = StudentClass::create($validated);
        $studentClass->load(['studyLevel', 'specialization']);
        
        return response()->json(['class' => $studentClass], 201);
    }

    public function show($id)
    {
        $studentClass = StudentClass::with(['studyLevel', 'specialization', 'students'])->findOrFail($id);
        return response()->json(['class' => $studentClass]);
    }

    public function update(Request $request, $id)
    {
        $studentClass = StudentClass::findOrFail($id);
        
        $validated = $request->validate([
            'study_level_id' => 'required|exists:study_levels,id',
            'specialization_id' => 'required|exists:specializations,id',
            'code' => 'required|string|max:50',
            'label' => 'required|string|max:255',
        ]);

        $studentClass->update($validated);
        $studentClass->load(['studyLevel', 'specialization']);
        
        return response()->json(['class' => $studentClass]);
    }

    public function destroy($id)
    {
        $studentClass = StudentClass::findOrFail($id);
        $studentClass->delete();
        
        return response()->json(['message' => 'Classe supprimée avec succès']);
    }
}
