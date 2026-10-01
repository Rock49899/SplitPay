<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentLoginRequest;
use App\Http\Requests\StudentVerifyOtpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\Student;
use App\Mail\StudentOtpMail;

class StudentAuthController extends Controller
{
    //client envoie matricule, on génère et envoie OTP par email
    public function requestOtp(StudentLoginRequest $request)
    {
        $matricule = $request->validated()['matricule'];

        $student = Student::where('matricule', $matricule)->first();
        if (! $student) {
            return response()->json(['message' => 'Matricule introuvable'], 404);
        }

        if (! $student->email) {
            return response()->json(['message' => 'Aucun email associé au matricule, impossible d\'envoyer OTP'], 422);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $key = "student_otp_{$student->id}";
        Cache::put($key, $otp, now()->addMinutes(10));
        Cache::forget("{$key}_attempts");

        // Envoi email — on capture les erreurs SMTP pour ne pas bloquer le login
        try {
            Mail::to($student->email)->send(new StudentOtpMail($otp, $student));
        } catch (\Throwable $e) {
            \Log::error('StudentAuthController: échec envoi OTP email', [
                'student_id' => $student->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'message'            => 'Impossible d\'envoyer l\'email OTP. Vérifiez la configuration SMTP.',
                'expires_in_minutes' => 10,
            ], 500);
        }

        return response()->json([
            'message' => 'Code OTP envoyé si l\'email est configuré',
            'expires_in_minutes' => 10,
        ], 202);
    }

    // Vérification de l'OTP : retourne token temporaire si OK
    public function verifyOtp(StudentVerifyOtpRequest $request)
    {
        $data = $request->validated();
        $matricule = $data['matricule'];
        $otp = $data['otp'];

        $student = Student::where('matricule', $matricule)->first();
        if (! $student) {
            return response()->json(['message' => 'Matricule introuvable'], 404);
        }

        $key = "student_otp_{$student->id}";
        $cached = Cache::get($key);
        if (! $cached || ! hash_equals((string)$cached, (string)$otp)) {
            $this->registerFailedOtpAttempt($key);
            return response()->json(['message' => 'OTP invalide ou expiré'], 401);
        }

        Cache::forget($key);
        Cache::forget("{$key}_attempts");

        // creer token de session  
        $token = Str::random(60);
        Cache::put("student_session_{$token}", $student->id, now()->addMinutes(60));

        return response()->json([
            'message' => 'Authentification réussie',
            'student' => $student,
            'token' => $token,
            'expires_in_minutes' => 60,
        ], 200);
    }

    /**
     * Après 5 essais erronés, le code OTP est invalidé (il faut en redemander un).
     */
    private function registerFailedOtpAttempt(string $key): void
    {
        $attemptsKey = "{$key}_attempts";
        Cache::add($attemptsKey, 0, now()->addMinutes(10));

        if (Cache::increment($attemptsKey) >= 5) {
            Cache::forget($key);
            Cache::forget($attemptsKey);
        }
    }

    // vérifier SI token en cache
    public function meByToken(Request $request)
    {
        $data = $request->validate(['token' => 'required|string']);
        $studentId = Cache::get('student_session_'.$data['token']);
        if (! $studentId) {
            return response()->json(['message' => 'Token invalide ou expiré'], 401);
        }
        $student = Student::find($studentId);
        if (! $student) {
            return response()->json(['message' => 'Étudiant introuvable'], 404);
        }
        return response()->json(['student' => $student], 200);
    }
}
