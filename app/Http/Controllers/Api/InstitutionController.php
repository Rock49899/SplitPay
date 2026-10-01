<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Models\Institution;
use App\Models\Enrollment;
use App\Models\PaymentLink;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class InstitutionController extends Controller
{
    use \App\Http\Controllers\Traits\FiltersByAnnexe;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    private function currentSchoolYear(): string
    {
        $now  = now();
        $y    = (int) $now->format('Y');
        $m    = (int) $now->format('n');
        return $m >= 9 ? "{$y}-" . ($y + 1) : ($y - 1) . "-{$y}";
    }

    private function ensureInstitutionSuperAdmin(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isSuperAdminInstitution()) {
            abort(403, 'Accès réservé au super administrateur institution.');
        }
    }

    /**
     * Retrouve une institution en vérifiant que l'utilisateur y a accès :
     * admin plateforme → toutes ; super admin institution → la sienne uniquement.
     */
    private function findAccessibleInstitution($id, array $with = []): Institution
    {
        $this->ensureInstitutionSuperAdmin();

        $query = Institution::with($with);

        if (! auth()->user()->isPlatformAdmin()) {
            $query->whereKey($this->getCurrentInstitutionId());
        }

        return $query->findOrFail($id);
    }

    // NOTE: in this SaaS instance the first registration creates the institution + default annexe + super-admin.
    // A full institutions CRUD is provided for administrative convenience but in typical single-tenant installs
    // only annexes will be managed after initial registration. Apply policies if you need to restrict create/destroy.

    // GET /api/admin/institutions
    public function index(Request $request)
    {
        $this->ensureInstitutionSuperAdmin();
        $institutionId = $this->getCurrentInstitutionId();

        if (method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin()) {
            $institutionId = $request->get('institution_id') ?: null;
        }

        if (! $institutionId && ! (method_exists(auth()->user(), 'isPlatformAdmin') && auth()->user()->isPlatformAdmin())) {
            return response()->json(['data' => [], 'total' => 0], 200);
        }

        $perPage = (int) $request->get('per_page', 15);
        $query = Institution::query();

        if ($institutionId) {
            $query->where('id', $institutionId);
        }

        if ($name = $request->get('name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if (!is_null($request->get('is_active'))) {
            $query->where('is_active', (bool) $request->get('is_active'));
        }

        return response()->json($query->orderBy('name')->paginate($perPage), 200);
    }

    // POST /api/admin/institutions
    public function store(StoreInstitutionRequest $request)
    {
        $v = $request->validated();

        $institution = Institution::create(array_merge($v, [
            'id' => (string) Str::uuid(),
            'is_active' => $v['is_active'] ?? true,
        ]));

        return response()->json(['message' => 'Institution created', 'institution' => $institution], 201);
    }

    // GET /api/admin/institutions/{id}
    public function show(Request $request, $id)
    {
        $this->ensureInstitutionSuperAdmin();

        $detailed = $request->boolean('detailed', false);

        // Backward compatibility for existing non-platform UIs:
        // return plain institution payload unless detailed mode is explicitly requested.
        if (! $detailed) {
            $institution = $this->findAccessibleInstitution($id);
            return response()->json($institution, 200);
        }

        $schoolYear = (string) $request->get('school_year', $this->currentSchoolYear());

        $institution = $this->findAccessibleInstitution($id, [
            'annexes' => function ($query) {
                $query
                    ->orderBy('name')
                    ->with([
                        'user_annexes.user:id,name,email',
                        'user_annexes.role:id,label,code',
                    ]);
            },
        ]);

        $availableYears = SchoolYear::query()
            ->forInstitution($institution->id)
            ->whereIn('status', ['active', 'closed'])
            ->orderByDesc('year')
            ->pluck('year')
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([$schoolYear]);
        }

        $annexeIds = $institution->annexes->pluck('id')->all();

        $enrollmentStatsByAnnexe = Enrollment::query()
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->whereIn('students.annexe_id', $annexeIds)
            ->where('enrollments.school_year', $schoolYear)
            ->select(
                'students.annexe_id',
                DB::raw('COUNT(DISTINCT enrollments.student_id) as students_total'),
                DB::raw('SUM(enrollments.tuition_amount) as tuition_expected'),
                DB::raw('SUM(enrollments.amount_paid) as payments_collected')
            )
            ->groupBy('students.annexe_id')
            ->get()
            ->keyBy('annexe_id');

        $activeStudentsByAnnexe = Student::query()
            ->whereIn('annexe_id', $annexeIds)
            ->where('status', 'active')
            ->select('annexe_id', DB::raw('COUNT(*) as active_students'))
            ->groupBy('annexe_id')
            ->pluck('active_students', 'annexe_id');

        $linksByAnnexe = PaymentLink::query()
            ->join('students', 'students.id', '=', 'payment_links.student_id')
            ->whereIn('students.annexe_id', $annexeIds)
            ->where('payment_links.school_year', $schoolYear)
            ->select('students.annexe_id', DB::raw('COUNT(payment_links.id) as links_total'))
            ->groupBy('students.annexe_id')
            ->pluck('links_total', 'annexe_id');

        $annexes = $institution->annexes->map(function ($annexe) use ($enrollmentStatsByAnnexe, $activeStudentsByAnnexe, $linksByAnnexe) {
            $responsables = $annexe->user_annexes
                ->filter(function ($ua) {
                    $code = $ua->role?->code;
                    return in_array($code, ['super_admin_annexe', 'gestionnaire', 'comptable'], true);
                })
                ->map(function ($ua) {
                    $roleLabel = $ua->role?->label ?? $ua->role?->name;

                    return [
                        'name' => $ua->user?->name,
                        'email' => $ua->user?->email,
                        'role' => $roleLabel,
                        'role_code' => $ua->role?->code,
                        'is_principal' => (bool) ($ua->is_principal ?? false),
                    ];
                })
                ->filter(fn ($item) => ! empty($item['name']) || ! empty($item['email']))
                ->values();

            $enrollmentStats = $enrollmentStatsByAnnexe[$annexe->id] ?? null;
            $totalStudents = (int) ($enrollmentStats->students_total ?? 0);
            $activeStudents = (int) ($activeStudentsByAnnexe[$annexe->id] ?? 0);
            $totalExpected = (float) ($enrollmentStats->tuition_expected ?? 0);
            $totalPaid = (float) ($enrollmentStats->payments_collected ?? 0);
            $linksCreated = (int) ($linksByAnnexe[$annexe->id] ?? 0);
            $recoveryRate = $totalExpected > 0
                ? round(($totalPaid / $totalExpected) * 100, 1)
                : 0.0;

            return [
                'id' => $annexe->id,
                'name' => $annexe->name,
                'city' => $annexe->city,
                'is_active' => (bool) $annexe->is_active,
                'responsables' => $responsables,
                'stats' => [
                    'students_total' => $totalStudents,
                    'students_active' => $activeStudents,
                    'tuition_expected' => $totalExpected,
                    'payments_collected' => $totalPaid,
                    'links_created' => $linksCreated,
                    'recovery_rate' => $recoveryRate,
                ],
            ];
        })->values();

        $tuitionExpected = (float) $annexes->sum('stats.tuition_expected');
        $paymentsCollected = (float) $annexes->sum('stats.payments_collected');
        $recoveryRate = $tuitionExpected > 0
            ? round(($paymentsCollected / $tuitionExpected) * 100, 1)
            : 0.0;

        $institutionStats = [
            'annexes_total' => $annexes->count(),
            'annexes_active' => $annexes->where('is_active', true)->count(),
            'students_total' => $annexes->sum('stats.students_total'),
            'students_active' => $annexes->sum('stats.students_active'),
            'tuition_expected' => $tuitionExpected,
            'payments_collected' => $paymentsCollected,
            'links_created' => $annexes->sum('stats.links_created'),
            'recovery_rate' => $recoveryRate,
        ];

        return response()->json([
            'school_year' => $schoolYear,
            'available_years' => $availableYears,
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'email' => $institution->email,
                'phone' => $institution->phone,
                'city' => $institution->city,
                'is_active' => (bool) $institution->is_active,
            ],
            'stats' => $institutionStats,
            'annexes' => $annexes,
        ], 200);
    }

    // PUT/PATCH /api/admin/institutions/{id}
    public function update(UpdateInstitutionRequest $request, $id)
    {
        $institution = $this->findAccessibleInstitution($id);
        $v = $request->validated();

        // Seul l'admin plateforme peut (dés)activer une institution
        if (! auth()->user()->isPlatformAdmin()) {
            unset($v['is_active']);
        }

        if ($request->boolean('remove_logo')) {
            if ($institution->logo) {
                Storage::disk('public')->delete($institution->logo);
            }
            $v['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($institution->logo) {
                Storage::disk('public')->delete($institution->logo);
            }
            $v['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $institution->update($v);

        return response()->json(['message' => 'Institution updated', 'institution' => $institution], 200);
    }

    // DELETE /api/admin/institutions/{id}
    public function destroy($id)
    {
        $institution = $this->findAccessibleInstitution($id);
        $institution->delete();
        return response()->json(['message' => 'Institution deleted'], 200);
    }

    // GET /api/admin/institutions/{id}/annexes
    public function annexes($id)
    {
        $institution = $this->findAccessibleInstitution($id, ['annexes']);
        return response()->json($institution->annexes, 200);
    }
}
