<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Support\Str;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\LevelFee;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class StudentController extends Controller
{
    use FiltersByAnnexe;
    
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Student::query();
        
        // IMPORTANT: Filtrer par annexe de l'utilisateur
        $query = $this->scopeByUserAnnexes($query);

        try {
            if ($search = $request->get('search') ?? $request->get('q')) {
                $searchable = ['first_name', 'last_name', 'email', 'matricule', 'student_number'];
                $available = array_filter($searchable, function ($col) {
                    return Schema::hasColumn('students', $col);
                });
                if (count($available)) {
                    $query->where(function ($q) use ($available, $search) {
                        foreach ($available as $col) {
                            $q->orWhere($col, 'like', "%{$search}%");
                        }
                    });
                }
            }

            if ($annexeId = $request->get('annexe_id')) {
                $query->where('annexe_id', $annexeId);
            }

            // filtrer par matricule si fourni et si la colonne existe
            if (($mat = $request->get('matricule')) && Schema::hasColumn('students', 'matricule')) {
                $query->where('matricule', 'like', "%{$mat}%");
            }

            if ($name = $request->get('name')) {
                $query->where(function ($q) use ($name) {
                    if (Schema::hasColumn('students', 'first_name')) {
                        $q->where('first_name', 'like', "%{$name}%");
                    }
                    if (Schema::hasColumn('students', 'last_name')) {
                        $q->orWhere('last_name', 'like', "%{$name}%");
                    }
                });
            }

            // filtrer par email correctement
            if (($email = $request->get('email')) && Schema::hasColumn('students', 'email')) {
                $query->where('email', 'like', "%{$email}%");
            }

            // filtrer par study_level_id (via enrollment)
            if ($studyLevelId = $request->get('study_level_id')) {
                $query->whereHas('enrollments.levelFee',
                    fn($q) => $q->where('study_level_id', $studyLevelId)
                );
            }

            // filtrer par specialization_id (colonne directe sur students)
            if ($specializationId = $request->get('specialization_id')) {
                $query->where('specialization_id', $specializationId);
            }

            // filtrer par année scolaire (via enrollments)
            if ($schoolYear = $request->get('school_year')) {
                $query->whereHas('enrollments', fn ($q) => $q->where('school_year', $schoolYear));
            }

            $students = $query->with(['annexe', 'specialization', 'currentEnrollment.levelFee.studyLevel'])
                ->orderBy('last_name')->paginate($perPage);

            return response()->json($students, 200);
        } catch (QueryException $e) {
            \Log::error('StudentController@index query failed', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['message' => 'Failed to fetch students.'], 500);
        }
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        // Extraire les champs d'enrollment (ne vont pas sur la table students)
        $studyLevelId     = $validated['study_level_id'] ?? null;
        $schoolYear       = $validated['school_year']    ?? null;
        $classId          = $validated['class_id']       ?? null;
        unset($validated['study_level_id'], $validated['school_year'], $validated['class_id'], $validated['tuition_amount']);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $student = Student::create(array_merge($validated, [
            'id'     => (string) Str::uuid(),
            'status' => 'active',
        ]));

        // Créer l'enrollment automatiquement si niveau + année fournis
        if ($studyLevelId && $schoolYear) {
            $fee = LevelFee::resolve(
                (int) $studyLevelId,
                $student->specialization_id ? (int) $student->specialization_id : null,
                $schoolYear
            );

            Enrollment::create([
                'student_id'     => $student->id,
                'level_fee_id'   => $fee?->id,   // null si aucun barème configuré
                'tuition_amount' => $fee?->tuition_amount ?? 0,
                'amount_paid'    => 0,
                'school_year'    => $schoolYear,
                'status'         => 'active',
            ]);
        }

        return response()->json([
            'message' => 'Student created',
            'student' => $student->load('specialization', 'currentEnrollment.levelFee.studyLevel'),
        ], 201);
    }

    // Retourne un étudiant avec ses relations et les données financières
    // issues de l'enrollment actif (source de vérité).
    public function show($id)
    {
        try {
            $student = Student::with([
                'annexe',
                'specialization',
                'currentEnrollment.levelFee.studyLevel',
                'enrollments.levelFee.studyLevel',
            ])->findOrFail($id);

            $enrollment = $student->currentEnrollment;
            $tuition    = (float) ($enrollment?->tuition_amount ?? 0);
            $amountPaid = (float) ($enrollment?->amount_paid    ?? 0);
            $amountDue  = max(0, $tuition - $amountPaid);

            $finance = [
                'tuition_amount'    => $tuition,
                'amount_paid'       => $amountPaid,
                'amount_due'        => $amountDue,
                'recovery_rate'     => $enrollment?->recovery_rate ?? 0,
                'school_year'       => $enrollment?->school_year,
                'study_level'       => $enrollment?->levelFee?->studyLevel?->label,
                'last_payment_date' => null,
            ];

            return response()->json(['student' => $student, 'finance' => $finance], 200);
        } catch (\Throwable $e) {
            \Log::error('StudentController@show failed', [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return response()->json(['message' => 'Failed to fetch student.'], 500);
        }
    }

    public function financials($id)
    {
        try {
            $student = Student::with([
                'currentEnrollment',
                'enrollments.levelFee.studyLevel',
            ])->findOrFail($id);

            $enrollment = $student->currentEnrollment;
            $tuition    = (float) ($enrollment?->tuition_amount ?? 0);
            $amountPaid = (float) ($enrollment?->amount_paid    ?? 0);
            $amountDue  = max(0, $tuition - $amountPaid);

            // Historique des paiements directs (via table payments)
            $payments = \App\Models\Payment::where('student_id', $student->id)
                ->orderByDesc('paid_at')
                ->take(10)
                ->get();

            return response()->json([
                'data' => [
                    'tuition_amount'    => $tuition,
                    'amount_paid'       => $amountPaid,
                    'amount_due'        => $amountDue,
                    'recovery_rate'     => $enrollment?->recovery_rate ?? 0,
                    'school_year'       => $enrollment?->school_year,
                    'last_payment_date' => $payments->first()?->paid_at?->toDateString(),
                    'recent_payments'   => $payments,
                    'history'           => $student->enrollments->map(fn($e) => [
                        'school_year'    => $e->school_year,
                        'study_level'    => $e->levelFee?->studyLevel?->label,
                        'tuition_amount' => $e->tuition_amount,
                        'amount_paid'    => $e->amount_paid,
                        'recovery_rate'  => $e->recovery_rate,
                        'status'         => $e->status,
                    ]),
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('StudentController@financials failed', [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return response()->json(['message' => 'Failed to fetch financials.'], 500);
        }
    }

    // Crée un lien de paiement basique (placeholder). En production, remplacer par logique prestataire & persistance.
    public function createPaymentLink(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $data = $request->validate(['amount' => 'required|numeric|min:0']);
        $link = url("/pay/student/{$student->id}?amount={$data['amount']}");
        return response()->json(['link' => $link], 201);
    }

    public function update(UpdateStudentRequest $request, $id)
    {
        $student   = Student::findOrFail($id);
        $validated = $request->validated();

        // Extraire les champs d'enrollment
        $studyLevelId = $validated['study_level_id'] ?? null;
        $schoolYear   = $validated['school_year']    ?? null;
        unset($validated['study_level_id'], $validated['school_year']);

        if ($request->hasFile('avatar')) {
            if ($student->avatar) {
                Storage::disk('public')->delete($student->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $student->update($validated);

        // Si le niveau ou l'année sont fournis, mettre à jour l'enrollment
        if ($studyLevelId && $schoolYear) {
            $fee = LevelFee::resolve(
                (int) $studyLevelId,
                $student->specialization_id ? (int) $student->specialization_id : null,
                $schoolYear
            );

            Enrollment::updateOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'level_fee_id'   => $fee?->id,
                    'tuition_amount' => $fee?->tuition_amount ?? 0,
                    // amount_paid n'est PAS écrasé
                ]
            );
        }

        return response()->json([
            'message' => 'Student updated',
            'student' => $student->fresh(['specialization', 'currentEnrollment.levelFee.studyLevel']),
        ], 200);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(['message' => 'Student deleted'], 200);
    }

    //importer liste des étudiants 
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        return response()->json(['message' => 'Import started'], 202);
    }
}
