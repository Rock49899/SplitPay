<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Student;

class StudentAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token requis. Veuillez vous connecter.'], 401);
        }

        $studentId = Cache::get("student_session_{$token}");

        if (!$studentId) {
            return response()->json(['message' => 'Session expirée ou invalide. Veuillez vous reconnecter.'], 401);
        }

        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['message' => 'Étudiant introuvable.'], 404);
        }

        // Attacher l'étudiant à la requête pour les contrôleurs
        $request->attributes->set('student', $student);

        return $next($request);
    }
}
