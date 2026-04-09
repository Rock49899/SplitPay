<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\FiltersByAnnexe;
use App\Models\Annexe;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    use FiltersByAnnexe;

    private function ensurePlatformAdmin(): void
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'isPlatformAdmin') || ! $user->isPlatformAdmin()) {
            abort(403, 'Accès réservé aux administrateurs plateforme.');
        }
    }

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

    private function schoolYearMonths(string $schoolYear): array
    {
        [$startY, $endY] = explode('-', $schoolYear);

        $months = [];
        for ($m = 9; $m <= 12; $m++) {
            $months[] = sprintf('%04d-%02d', (int) $startY, $m);
        }
        for ($m = 1; $m <= 8; $m++) {
            $months[] = sprintf('%04d-%02d', (int) $endY, $m);
        }

        return $months;
    }

    private function schoolYearLabels(string $schoolYear): array
    {
        $months = $this->schoolYearMonths($schoolYear);
        $monthNames = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
        ];

        return array_map(function ($ym) use ($monthNames) {
            $month = (int) explode('-', $ym)[1];
            return $monthNames[$month] ?? $ym;
        }, $months);
    }

    /**
     * Build a Payment query scoped to role + optional date range.
     */
    private function paymentsQuery(?string $from = null, ?string $to = null)
    {
        $q = Payment::query();

        if (!$this->isSuperAdminInstitution()) {
            $ids = $this->getAccessibleAnnexeIds();
            $q->whereHas('student', fn($s) => $s->whereIn('annexe_id', $ids));
        } else {
            $ids = $this->getAccessibleAnnexeIds();
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
                $s->whereIn('annexe_id', $this->getAccessibleAnnexeIds());
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
            ? count($this->getAccessibleAnnexeIds())
            : count($this->getAccessibleAnnexeIds());

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
        $ids = $this->getAccessibleAnnexeIds();
        $annexeQuery->whereIn('id', $ids);
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
            ->when(true, function ($q) {
                $ids = $this->getAccessibleAnnexeIds();
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
            ->when(true, function ($q) {
                $ids = $this->getAccessibleAnnexeIds();
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

    /**
     * GET /admin/dashboard/platform-overview
     *
     * KPI + séries dédiés au tableau de bord plateforme.
     */
    public function platformOverview(Request $request)
    {
        $this->ensurePlatformAdmin();

        $schoolYear = $request->get('school_year', $this->currentSchoolYear());
        [$from, $to] = $this->schoolYearRange($schoolYear);

        $availableYears = SchoolYear::query()
            ->whereIn('status', ['active', 'closed'])
            ->orderByDesc('year')
            ->pluck('year')
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([$schoolYear]);
        }

        $annexeIds = $this->getAccessibleAnnexeIds();
        $labels = $this->schoolYearLabels($schoolYear);
        $months = $this->schoolYearMonths($schoolYear);

        if (empty($annexeIds)) {
            return response()->json([
                'school_year' => $schoolYear,
                'available_years' => $availableYears,
                'kpis' => [
                    'recovery_rate' => 0,
                    'links_created' => 0,
                    'links_used' => 0,
                    'total_paid' => 0,
                    'total_remaining' => 0,
                ],
                'charts' => [
                    'labels' => $labels,
                    'recovery_rate' => array_fill(0, count($labels), 0),
                    'links_created' => array_fill(0, count($labels), 0),
                    'links_used' => array_fill(0, count($labels), 0),
                    'paid' => array_fill(0, count($labels), 0),
                    'remaining' => array_fill(0, count($labels), 0),
                ],
            ]);
        }

        $enrollmentQ = Enrollment::query()
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->whereIn('students.annexe_id', $annexeIds)
            ->where('students.status', 'active');

        if (Schema::hasColumn('enrollments', 'school_year')) {
            $enrollmentQ->where('enrollments.school_year', $schoolYear);
        }

        $totalTuition = (float) (clone $enrollmentQ)->sum('enrollments.tuition_amount');
        $totalPaidFromEnrollments = (float) (clone $enrollmentQ)->sum('enrollments.amount_paid');
        $totalRemaining = max(0, $totalTuition - $totalPaidFromEnrollments);

        $linksQ = PaymentLink::query()
            ->join('students', 'students.id', '=', 'payment_links.student_id')
            ->whereIn('students.annexe_id', $annexeIds)
            ->whereBetween('payment_links.created_at', [$from, $to]);

        if (Schema::hasColumn('payment_links', 'school_year')) {
            $linksQ->where('payment_links.school_year', $schoolYear);
        }

        $linksCreated = (int) (clone $linksQ)->count('payment_links.id');
        $linksUsed = (int) (clone $linksQ)->where('payment_links.status', 'used')->count('payment_links.id');

        $paymentsMonthly = Payment::query()
            ->join('students', 'students.id', '=', 'payments.student_id')
            ->whereIn('students.annexe_id', $annexeIds)
            ->whereBetween('payments.paid_at', [$from, $to])
            ->where('payments.status', 'success')
            ->select(
                DB::raw("DATE_FORMAT(payments.paid_at, '%Y-%m') as ym"),
                DB::raw('SUM(payments.amount) as total')
            )
            ->groupBy('ym')
            ->get()
            ->pluck('total', 'ym');

        $linksCreatedMonthly = (clone $linksQ)
            ->select(
                DB::raw("DATE_FORMAT(payment_links.created_at, '%Y-%m') as ym"),
                DB::raw('COUNT(payment_links.id) as total')
            )
            ->groupBy('ym')
            ->get()
            ->pluck('total', 'ym');

        $linksUsedMonthly = (clone $linksQ)
            ->where('payment_links.status', 'used')
            ->select(
                DB::raw("DATE_FORMAT(payment_links.created_at, '%Y-%m') as ym"),
                DB::raw('COUNT(payment_links.id) as total')
            )
            ->groupBy('ym')
            ->get()
            ->pluck('total', 'ym');

        $paidSeries = [];
        $remainingSeries = [];
        $linksCreatedSeries = [];
        $linksUsedSeries = [];
        $recoverySeries = [];

        $cumulativePaid = 0.0;
        foreach ($months as $ym) {
            $monthPaid = (float) ($paymentsMonthly[$ym] ?? 0);
            $monthLinksCreated = (int) ($linksCreatedMonthly[$ym] ?? 0);
            $monthLinksUsed = (int) ($linksUsedMonthly[$ym] ?? 0);

            $cumulativePaid += $monthPaid;
            $remaining = max(0, $totalTuition - $cumulativePaid);
            $monthRecovery = $totalTuition > 0
                ? round(($cumulativePaid / $totalTuition) * 100, 1)
                : 0;

            $paidSeries[] = round($cumulativePaid, 2);
            $remainingSeries[] = round($remaining, 2);
            $linksCreatedSeries[] = $monthLinksCreated;
            $linksUsedSeries[] = $monthLinksUsed;
            $recoverySeries[] = $monthRecovery;
        }

        $recoveryRate = $totalTuition > 0
            ? round(($totalPaidFromEnrollments / $totalTuition) * 100, 1)
            : 0;

        return response()->json([
            'school_year' => $schoolYear,
            'available_years' => $availableYears,
            'kpis' => [
                'recovery_rate' => $recoveryRate,
                'links_created' => $linksCreated,
                'links_used' => $linksUsed,
                'total_paid' => round($totalPaidFromEnrollments, 2),
                'total_remaining' => round($totalRemaining, 2),
            ],
            'charts' => [
                'labels' => $labels,
                'recovery_rate' => $recoverySeries,
                'links_created' => $linksCreatedSeries,
                'links_used' => $linksUsedSeries,
                'paid' => $paidSeries,
                'remaining' => $remainingSeries,
            ],
        ]);
    }
}
