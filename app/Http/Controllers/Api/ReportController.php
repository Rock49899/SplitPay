<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // simple dashboard summary
    public function summary(Request $request)
    {
        return response()->json([
            'users_count' => User::count(),
            'students_count' => Student::count(),
            'payments_count' => Payment::count(),
            'payments_sum' => (float) Payment::sum('amount'),
        ], 200);
    }
}
