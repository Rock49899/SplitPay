<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\Annexe;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use FiltersByAnnexe;

    // ─── Helpers year ──────────────────────────────────────────────────────────

    private function currentSchoolYear(): string
    {
        $now  = now();
        $y    = (int) $now->format('Y');
        $m    = (int) $now->format('n');
        return $m >= 9 ? "{$y}-" . ($y + 1) : ($y - 1) . "-{$y}";
    }

    /**
     * Return [start, end] Carbon objects for an academic year like "2025-2026".
     * Academic year: Sept 1 → Aug 31 of following year.
     */
    private function schoolYearRange(string $schoolYear): array
    {
        [$startY, $endY] = explode('-', $schoolYear);
        return [
            \Carbon\Carbon::create((int) $startY, 9, 1, 0, 0, 0),
            \Carbon\Carbon::create((int) $endY,   8, 31, 23, 59, 59),
        ];
    }

    /**
     * Build a Payment query scoped to role + optional date range.
     */
    private function paymentsQuery(?string $from = null, ?string $to = null)
    {
        $q = Payment::query();

        if (!$this->isSuperAdminInstitution()) {
            $ids = $this->getUserAnnexeIds();
            $q->whereHas('student', fn($s) => $s->whereIn('annexe_id', $ids));
        }

        if ($from) $q->where('paid_at', '>=', $from);
        if ($to)   $q->where('paid_at', '<=', $to);

        return $q;
    }

    /**
     * Build an Enrollment query scoped to role + school_year.
     */
    private function enrollmentsQuery(string $schoolYear)
    {
        $q = Enrollment::where('school_year', $schoolYear)
            ->whereHas('student', function ($s) {
                $s->where('status', 'active');
                if (!$this->isSuperAdminInstitution()) {
                    $s->whereIn('annexe_id', $this->getUserAnnexeIds());
                }
            });

        return $q;
    }

    // ─── Endpoints ─────────────────────────────────────────────────────────────

    /**
     * GET /admin/dashboard/kpis
     *
     * Returns the 6 main KPIs, scoped by role.
     * Query params:
     *   school_year  string  "2025-2026" (default: current)
     */
    public function kpis(Request $request)
    {
        $schoolYear  = $request->get('school_year', $this->currentSchoolYear());

        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'school_year'     => $schoolYear,
                'total_collected' => 0,
                'total_pending'   => 0,
                'total_unpaid'    => 0,
                'total_tuition'   => 0,
                'students_count'  => 0,
                'annexes_count'   => 0,
                'recovery_rate'   => 0,
            ]);
        }

        [$from, $to] = $this->schoolYearRange($schoolYear);

        // ── Enrollment sums for the academic year ─────────────────────────────
        $enrollQ       = $this->enrollmentsQuery($schoolYear);
        $totalTuition  = (float) (clone $enrollQ)->sum('tuition_amount');
        $totalPaid     = (float) (clone $enrollQ)->sum('amount_paid');
        $totalUnpaid   = max(0, $totalTuition - $totalPaid);
        // unique constraint (student_id, school_year) → count() = nb étudiants inscrits
        $studentsCount = (clone $enrollQ)->count();

        // ── Payments scoped to year + role ────────────────────────────────────
        $collected = $this->paymentsQuery($from, $to)
            ->where('status', 'success')
            ->sum('amount');

        $pending   = $this->paymentsQuery($from, $to)
            ->where('status', 'pending')
            ->sum('amount');

        // ── Recovery rate ─────────────────────────────────────────────────────
        $recoveryRate = $totalTuition > 0
            ? round(((float) $totalPaid / (float) $totalTuition) * 100, 1)
            : 0;

        // ── Annexes count ─────────────────────────────────────────────────────
        $annexesCount = $this->isSuperAdminInstitution()
            ? Annexe::where('is_active', true)->count()
            : count($this->getUserAnnexeIds());

        return response()->json([
            'school_year'     => $schoolYear,
            'total_collected' => (float) $collected,
            'total_pending'   => (float) $pending,
            'total_unpaid'    => $totalUnpaid,
            'total_tuition'   => (float) $totalTuition,
            'students_count'  => $studentsCount,
            'annexes_count'   => $annexesCount,
            'recovery_rate'   => $recoveryRate,
        ]);
    }

    /**
     * GET /admin/dashboard/monthly-collections
     *
     * Returns monthly collected & pending amounts for the academic year.
     * Months: Sep → Aug (12 months).
     */
    public function monthlyCollections(Request $request)
    {
        $schoolYear  = $request->get('school_year', $this->currentSchoolYear());

        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'school_year' => $schoolYear,
                'labels' => [],
                'series' => [
                    ['name' => 'Collected', 'data' => []],
                    ['name' => 'Pending', 'data' => []],
                ],
            ]);
        }

        [$from, $to] = $this->schoolYearRange($schoolYear);

        // Base query with role scope
        $baseQ = Payment::query()
            ->whereBetween('paid_at', [$from, $to]);

        if (!$this->isSuperAdminInstitution()) {
            $ids = $this->getUserAnnexeIds();
            $baseQ->whereHas('student', fn($s) => $s->whereIn('annexe_id', $ids));
        }

        // Group by year-month, split by status
        $rows = (clone $baseQ)
            ->select(
                DB::raw("DATE_FORMAT(paid_at, '%Y-%m') as ym"),
                'status',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('ym', 'status')
            ->get();

        // Build month labels: Sep(year1)…Aug(year2)
        [$startY, $endY] = explode('-', $schoolYear);
        $months = [];
        for ($m = 9; $m <= 12; $m++) {
            $months[] = sprintf('%04d-%02d', (int) $startY, $m);
        }
        for ($m = 1; $m <= 8; $m++) {
            $months[] = sprintf('%04d-%02d', (int) $endY, $m);
        }

        $collectedMap = $rows->where('status', 'success')->pluck('total', 'ym');
        $pendingMap   = $rows->where('status', 'pending')->pluck('total', 'ym');

        $labels    = [];
        $collected = [];
        $pending   = [];

        $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        foreach ($months as $ym) {
            $month       = (int) explode('-', $ym)[1];
            $labels[]    = $monthNames[$month];
            $collected[] = (float) ($collectedMap[$ym] ?? 0);
            $pending[]   = (float) ($pendingMap[$ym]   ?? 0);
        }

        return response()->json([
            'school_year' => $schoolYear,
            'labels'      => $labels,
            'series' => [
                ['name' => 'Collected', 'data' => $collected],
                ['name' => 'Pending',   'data' => $pending],
            ],
        ]);
    }

    /**
     * GET /admin/dashboard/annexe-stats
     *
     * Per-annexe: collected, tuition_total, unpaid amount, recovery rate.
     * Only meaningful for super_admin_institution (sees all); others see their scope.
     */
    public function annexeStats(Request $request)
    {
        $schoolYear  = $request->get('school_year', $this->currentSchoolYear());

        if ($request->attributes->get('school_year_available') === false) {
            return response()->json([
                'school_year'   => $schoolYear,
                'labels'        => [],
                'collected'     => [],
                'recovery_rate' => [],
                'unpaid'        => [],
            ]);
        }

        [$from, $to] = $this->schoolYearRange($schoolYear);

        // Determine which annexes to include
        $annexeQuery = Annexe::where('is_active', true);
        if (!$this->isSuperAdminInstitution()) {
            $ids = $this->getUserAnnexeIds();
            $annexeQuery->whereIn('id', $ids);
        }
        $annexes = $annexeQuery->get(['id', 'name']);

        // Enrollment sums per annexe for the given school year
        $studentSums = Enrollment::select(
                'students.annexe_id',
                DB::raw('COUNT(DISTINCT enrollments.student_id) as count'),
                DB::raw('SUM(enrollments.tuition_amount) as tuition_total'),
                DB::raw('SUM(enrollments.amount_paid) as paid_total')
            )
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.school_year', $schoolYear)
            ->where('students.status', 'active')
            ->when(!$this->isSuperAdminInstitution(), function ($q) {
                $ids = $this->getUserAnnexeIds();
                $q->whereIn('students.annexe_id', $ids);
            })
            ->groupBy('students.annexe_id')
            ->get()
            ->keyBy('annexe_id');

        // Payments collected per annexe (via student.annexe_id)
        $collectedPerAnnexe = Payment::select(
                'students.annexe_id',
                DB::raw('SUM(payments.amount) as collected')
            )
            ->join('students', 'students.id', '=', 'payments.student_id')
            ->where('payments.status', 'success')
            ->whereBetween('payments.paid_at', [$from, $to])
            ->when(!$this->isSuperAdminInstitution(), function ($q) {
                $ids = $this->getUserAnnexeIds();
                $q->whereIn('students.annexe_id', $ids);
            })
            ->groupBy('students.annexe_id')
            ->get()
            ->keyBy('annexe_id');

        $labels       = [];
        $collected    = [];
        $recoveryRate = [];
        $unpaid       = [];

        foreach ($annexes as $annexe) {
            $sums      = $studentSums[$annexe->id]  ?? null;
            $coll      = (float) ($collectedPerAnnexe[$annexe->id]->collected ?? 0);
            $tuition   = $sums ? (float) $sums->tuition_total : 0;
            $paidTotal = $sums ? (float) $sums->paid_total    : 0;
            $rate      = $tuition > 0 ? round(($paidTotal / $tuition) * 100, 1) : 0;

            $labels[]       = $annexe->name;
            $collected[]    = $coll;
            $recoveryRate[] = $rate;
            $unpaid[]       = max(0, $tuition - $paidTotal);
        }

        return response()->json([
            'school_year'   => $schoolYear,
            'labels'        => $labels,
            'collected'     => $collected,
            'recovery_rate' => $recoveryRate,
            'unpaid'        => $unpaid,
        ]);
    }
}
