<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Student::query();

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

            if ($mat = $request->get('matricule') && Schema::hasColumn('students', 'matricule')) {
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

            if ($email = $request->get('email') && Schema::hasColumn('students', 'email')) {
                $query->where('email', 'like', "%{$email}%");
            }

            if ($class = $request->get('class') && Schema::hasColumn('students', 'class')) {
                $query->where('class', $class);
            }

            if ($year = $request->get('school_year') && Schema::hasColumn('students', 'school_year')) {
                $query->where('school_year', $year);
            }

            $students = $query->orderBy('last_name')->paginate($perPage);

            return response()->json($students, 200);
        } catch (QueryException $e) {
            \Log::error('StudentController@index query failed', ['error' => $e->getMessage(), 'request' => $request->all()]);
            return response()->json(['message' => 'Failed to fetch students.'], 500);
        }
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        $student = Student::create(array_merge($validated, [
            'id' => (string) Str::uuid(),
            'amount_paid' => 0,
            'status' => 'active',
        ]));

        return response()->json(['message' => 'Student created', 'student' => $student], 201);
    }

    // Return a student with relations needed by frontend
    public function show($id)
    {
        try {
            $studentModel = new Student();
            $with = [];
            if (method_exists($studentModel, 'annexe')) $with[] = 'annexe';
            if (method_exists($studentModel, 'payments')) $with[] = 'payments';
            if (method_exists($studentModel, 'paymentLinks')) $with[] = 'paymentLinks.installments.payments';

            $student = count($with) ? Student::with($with)->findOrFail($id) : Student::findOrFail($id);
            return response()->json(['student' => $student], 200);
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

            // récupérer les paiements depuis la relation payments si elle existe
            $payments = collect([]);
            if (method_exists($student, 'payments')) {
                $student->loadMissing('payments');
                $payments = $student->payments ?? collect([]);
            } elseif (method_exists($student, 'paymentLinks')) {
                // fallback : parcourir paymentLinks -> installments -> payments
                $student->loadMissing('paymentLinks.installments.payments');
                $payments = collect([]);
                foreach ($student->paymentLinks ?? [] as $pl) {
                    foreach ($pl->installments ?? [] as $inst) {
                        if (is_iterable($inst->payments)) {
                            $payments = $payments->concat($inst->payments);
                        }
                    }
                }
            }

            $amountPaid = $payments->sum('amount');
            $tuition = $student->tuition_amount ?? 0;
            $amountDue = max(0, $tuition - $amountPaid);
            $lastPayment = $payments->sortByDesc('created_at')->first();

            return response()->json([
                'data' => [
                    'tuition_amount' => $tuition,
                    'amount_paid' => $amountPaid,
                    'amount_due' => $amountDue,
                    'last_payment_date' => $lastPayment ? ($lastPayment->created_at->toDateString() ?? $lastPayment->created_at ?? null) : null,
                    'recent_payments' => $payments->sortByDesc('created_at')->take(10)->values(),
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
