<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Support\Str;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use App\Models\Student;
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

            // filtrer par study_level_id
            if ($studyLevelId = $request->get('study_level_id')) {
                $query->where('study_level_id', $studyLevelId);
            }

            // filtrer par specialization_id
            if ($specializationId = $request->get('specialization_id')) {
                $query->where('specialization_id', $specializationId);
            }

            $students = $query->with(['annexe', 'studyLevel', 'specialization'])->orderBy('last_name')->paginate($perPage);

            return response()->json($students, 200);
        } catch (QueryException $e) {
            \Log::error('StudentController@index query failed', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['message' => 'Failed to fetch students.'], 500);
        }
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $student = Student::create(array_merge($validated, [
            'id' => (string) Str::uuid(),
            'amount_paid' => 0,
            'status' => 'active',
        ]));

        return response()->json(['message' => 'Student created', 'student' => $student], 201);
    }

    // Retourne un étudiant avec les relations nécessaires au frontend
    // et les informations financières calculées à partir des champs stockés en base
    public function show($id)
    {
        try {
            $studentModel = new Student();
            $with = [];
            if (method_exists($studentModel, 'annexe')) $with[] = 'annexe';
            if (method_exists($studentModel, 'studyLevel')) $with[] = 'studyLevel';
            if (method_exists($studentModel, 'specialization')) $with[] = 'specialization';
            if (method_exists($studentModel, 'payments')) $with[] = 'payments';
            if (method_exists($studentModel, 'paymentLinks')) $with[] = 'paymentLinks.installments.payments';

            $student = count($with) ? Student::with($with)->findOrFail($id) : Student::findOrFail($id);

            // Utilise tuition_amount et amount_paid tels qu'ils sont stockés dans la table students
            $tuition = (float) ($student->tuition_amount ?? 0);
            $amountPaid = (float) ($student->amount_paid ?? 0);

            // Si amount_paid est absent ou zéro, on cherche les paiements chargés en relation
            // et on fait la somme comme solution de secours
            if ($amountPaid <= 0 && method_exists($student, 'payments') && $student->relationLoaded('payments')) {
                $amountPaid = (float) collect($student->payments)->sum(function ($p) {
                    return (float) ($p->amount ?? $p['amount'] ?? 0);
                });
            }

            $amountDue = max(0, $tuition - $amountPaid);
            $lastPayment = null;
            if (method_exists($student, 'payments') && $student->relationLoaded('payments')) {
                $lastPayment = collect($student->payments)->sortByDesc('created_at')->first();
            }

            $finance = [
                'tuition_amount'   => $tuition,
                'amount_paid'      => $amountPaid,
                'amount_due'       => $amountDue,
                'last_payment_date'=> $lastPayment ? ($lastPayment->created_at->toDateString() ?? $lastPayment->created_at ?? null) : null,
            ];

            return response()->json(['student' => $student, 'finance' => $finance], 200);
        } catch (\Throwable $e) {
            \Log::error('StudentController@show failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
            ]);
            return response()->json(['message' => 'Failed to fetch student.'], 500);
        }
    }

    public function financials($id)
    {
        try {
            $student = Student::findOrFail($id);

            // Privilégier amount_paid stocké dans la table students
            $amountPaid = (float) ($student->amount_paid ?? 0);

            // Si amount_paid stocké est nul, on calcule la somme des paiements existants en repli
            if ($amountPaid <= 0) {
                if (method_exists($student, 'payments')) {
                    $student->loadMissing('payments');
                    $payments = $student->payments ?? collect([]);
                    $amountPaid = (float) $payments->sum(function ($p) {
                        return (float) ($p->amount ?? $p['amount'] ?? 0);
                    });
                    $lastPayment = $payments->sortByDesc('created_at')->first();
                } elseif (method_exists($student, 'paymentLinks')) {

                $student->loadMissing('paymentLinks.installments.payments');
                    $payments = collect([]);
                    foreach ($student->paymentLinks ?? [] as $pl) {
                        foreach ($pl->installments ?? [] as $inst) {
                            if (is_iterable($inst->payments)) {
                                $payments = $payments->concat($inst->payments);
                            }
                        }
                    }
                    $amountPaid = (float) $payments->sum(function ($p) {
                        return (float) ($p->amount ?? $p['amount'] ?? 0);
                    });
                    $lastPayment = $payments->sortByDesc('created_at')->first();
                } else {
                    $lastPayment = null;
                }
            } else {
                // si amount_paid est présent en base, tenter de récupérer la date du dernier paiement si relation disponible
                $lastPayment = null;
                if (method_exists($student, 'payments')) {
                    $student->loadMissing('payments');
                    $lastPayment = $student->payments ? collect($student->payments)->sortByDesc('created_at')->first() : null;
                }
            }

            $tuition = (float) ($student->tuition_amount ?? 0);
            $amountDue = max(0, $tuition - $amountPaid);

            return response()->json([
                'data' => [
                    'tuition_amount' => $tuition,
                    'amount_paid' => $amountPaid,
                    'amount_due' => $amountDue,
                    'last_payment_date' => $lastPayment ? ($lastPayment->created_at->toDateString() ?? $lastPayment->created_at ?? null) : null,
                    'recent_payments' => isset($payments) ? collect($payments)->sortByDesc('created_at')->take(10)->values() : [],
                ]
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('StudentController@financials failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $id,
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
        $student = Student::findOrFail($id);
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($student->avatar) {
                Storage::disk('public')->delete($student->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $student->update($validated);

        return response()->json(['message' => 'Student updated', 'student' => $student], 200);
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
