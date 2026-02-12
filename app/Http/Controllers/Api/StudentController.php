<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use Illuminate\Http\Request;
use App\Models\Student;

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

        if ($annexeId = $request->get('annexe_id')) {
            $query->where('annexe_id', $annexeId);
        }

        if ($mat = $request->get('matricule')) {
            $query->where('matricule', 'like', "%{$mat}%");
        }

        if ($name = $request->get('name')) {
            $query->where(function($q) use ($name) {
                $q->where('first_name', 'like', "%{$name}%")
                  ->orWhere('last_name', 'like', "%{$name}%");
            });
        }

        if ($email = $request->get('email')) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($class = $request->get('class')) {
            $query->where('class', $class);
        }

        if ($year = $request->get('school_year')) {
            $query->where('school_year', $year);
        }

        $students = $query->orderBy('last_name')->paginate($perPage);

        return response()->json($students, 200);
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
        // eager load annex relations and payments (adjust relation names to your models)
        $student = Student::with(['annexe','annexes','student_annexes.annexe','payments'])->findOrFail($id);
        return response()->json(['student' => $student], 200);
    }

    public function financials($id)
    {
        $student = Student::with('payments')->findOrFail($id);

        // compute summary (adjust fields according to your DB)
        $payments = $student->payments ?? collect([]);
        $amountPaid = $payments->sum('amount');
        $tuition = $student->tuition_amount ?? 0;
        $amountDue = max(0, $tuition - $amountPaid);
        $lastPayment = $payments->sortByDesc('created_at')->first();

        return response()->json([
            'data' => [
                'tuition_amount' => $tuition,
                'amount_paid' => $amountPaid,
                'amount_due' => $amountDue,
                'last_payment_date' => $lastPayment ? $lastPayment->created_at->toDateString() : null,
                'recent_payments' => $payments->sortByDesc('created_at')->take(10)->values(),
            ]
        ], 200);
    }

    // Optional: create payment link (backend implementation depends on payment provider)
    public function createPaymentLink(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $data = $request->validate(['amount' => 'required|numeric|min:0']);
        // implement provider logic; here we return a dummy link for frontend
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
