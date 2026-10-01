<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;

class ReportController extends Controller
{
    use FiltersByAnnexe;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // simple dashboard summary (Student et Payment sont restreints par leur scope global)
    public function summary(Request $request)
    {
        $users = User::query();

        if (! auth()->user()->isPlatformAdmin()) {
            $annexeIds = $this->getAccessibleAnnexeIds();
            $users->whereHas('annexes', fn ($q) => $q->whereIn('annexes.id', $annexeIds));
        }

        return response()->json([
            'users_count' => $users->count(),
            'students_count' => Student::count(),
            'payments_count' => Payment::count(),
            'payments_sum' => (float) Payment::where('status', 'success')->sum('amount'),
        ], 200);
    }
}
