<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Met à jour un enrollment (ex: ajustement du tuition_amount pour réduction).
     * PATCH admin/enrollments/{enrollment}
     */
    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        $data = $request->validate([
            'tuition_amount' => 'sometimes|numeric|min:0',
            'amount_paid'    => 'sometimes|numeric|min:0',
            'status'         => 'sometimes|in:active,completed,abandoned',
            'notes'          => 'nullable|string',
        ]);

        $enrollment->update($data);

        return response()->json([
            'data' => $enrollment->fresh(['levelFee.studyLevel', 'levelFee.specialization']),
        ]);
    }

    /**
     * Retourne les enrollments d'un étudiant (historique complet).
     * GET admin/enrollments?student_id=xxx
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['student_id' => 'required|uuid|exists:students,id']);

        $enrollments = Enrollment::with(['levelFee.studyLevel', 'levelFee.specialization'])
            ->where('student_id', $request->student_id)
            ->orderByDesc('school_year')
            ->get()
            ->map(fn($e) => [
                'id'             => $e->id,
                'school_year'    => $e->school_year,
                'study_level'    => $e->levelFee?->studyLevel?->label,
                'specialization' => $e->levelFee?->specialization?->label,
                'tuition_amount' => $e->tuition_amount,
                'amount_paid'    => $e->amount_paid,
                'remaining'      => $e->remaining,
                'recovery_rate'  => $e->recovery_rate,
                'status'         => $e->status,
                'promoted_at'    => $e->promoted_at,
            ]);

        return response()->json(['data' => $enrollments]);
    }
}
