<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;

class SpecializationController extends Controller
{
    public function index(Request $request)
    {
        $query = Specialization::query()->orderBy('label');
        
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
            'code' => 'required|string|max:50|unique:specializations,code',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $specialization = Specialization::create($validated);
        
        return response()->json(['specialization' => $specialization], 201);
    }

    public function show($id)
    {
        $specialization = Specialization::findOrFail($id);
        return response()->json(['specialization' => $specialization]);
    }

    public function update(Request $request, $id)
    {
        $specialization = Specialization::findOrFail($id);
        
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:specializations,code,' . $id,
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $specialization->update($validated);
        
        return response()->json(['specialization' => $specialization]);
    }

    public function destroy($id)
    {
        $specialization = Specialization::findOrFail($id);
        $specialization->delete();
        
        return response()->json(['message' => 'Spécialisation supprimée avec succès']);
    }
}
