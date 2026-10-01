<?php

namespace Tests\Feature;

use App\Models\Annexe;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un super admin institution voit toutes les annexes de SON institution,
 * et jamais les données d'une autre institution.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminA;
    private Annexe $annexeA1;
    private Annexe $annexeA2;
    private Annexe $annexeB;
    private Institution $institutionB;
    private Student $studentA1;
    private Student $studentA2;
    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'code' => 'super_admin_institution',
            'label' => 'Super Admin Institution',
            'scope' => 'institution',
        ]);

        foreach (['student.view', 'payment.view', 'annexe.view'] as $code) {
            $permission = Permission::create(['code' => $code, 'label' => $code, 'module' => 'test']);
            $role->permissions()->attach($permission->id);
        }

        $institutionA = Institution::create(['name' => 'Institution A', 'email' => 'contact@institution-a.test', 'is_active' => true]);
        $this->institutionB = Institution::create(['name' => 'Institution B', 'email' => 'contact@institution-b.test', 'is_active' => true]);

        $this->annexeA1 = Annexe::create(['institution_id' => $institutionA->id, 'name' => 'A1', 'is_active' => true]);
        $this->annexeA2 = Annexe::create(['institution_id' => $institutionA->id, 'name' => 'A2', 'is_active' => true]);
        $this->annexeB = Annexe::create(['institution_id' => $this->institutionB->id, 'name' => 'B1', 'is_active' => true]);

        $this->adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin.a@example.com',
            'password' => 'secret-password',
            'scope' => 'institution',
            'is_active' => true,
        ]);
        // Assigné uniquement à A1 : A2 doit rester visible car elle appartient à son institution
        $this->adminA->assignToAnnexe($this->annexeA1->id, $role->id, true);

        $this->studentA1 = $this->makeStudentWithPayment($this->annexeA1, 'MAT-A1');
        $this->studentA2 = $this->makeStudentWithPayment($this->annexeA2, 'MAT-A2');
        $this->studentB = $this->makeStudentWithPayment($this->annexeB, 'MAT-B1');
    }

    private function makeStudentWithPayment(Annexe $annexe, string $matricule): Student
    {
        $student = Student::create([
            'annexe_id' => $annexe->id,
            'matricule' => $matricule,
            'first_name' => 'Etudiant',
            'last_name' => $matricule,
            'email' => strtolower($matricule) . '@etudiant.test',
            'status' => 'active',
        ]);

        $link = PaymentLink::create([
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_' . $matricule,
            'amount' => 1000,
            'currency' => 'XOF',
            'status' => 'active',
        ]);

        Payment::create([
            'payment_link_id' => $link->id,
            'student_id' => $student->id,
            'amount' => 1000,
            'method' => 'mtn',
            'reference' => 'REF-' . $matricule,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        return $student;
    }

    public function test_institution_admin_sees_all_annexes_of_own_institution_only(): void
    {
        $this->actingAs($this->adminA, 'sanctum');

        $ids = collect($this->getJson('/api/admin/students')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($this->studentA1->id));
        $this->assertTrue($ids->contains($this->studentA2->id));
        $this->assertFalse($ids->contains($this->studentB->id));
    }

    public function test_institution_admin_cannot_read_student_of_other_institution(): void
    {
        $this->actingAs($this->adminA, 'sanctum');

        $this->getJson("/api/admin/students/{$this->studentA2->id}")->assertOk();
        $this->getJson("/api/admin/students/{$this->studentB->id}")->assertNotFound();
    }

    public function test_institution_admin_only_sees_payments_of_own_institution(): void
    {
        $this->actingAs($this->adminA, 'sanctum');

        $references = collect($this->getJson('/api/admin/payments')->assertOk()->json('data'))->pluck('reference');

        $this->assertEqualsCanonicalizing(['REF-MAT-A1', 'REF-MAT-A2'], $references->all());
    }

    public function test_institution_admin_cannot_switch_to_annexe_of_other_institution(): void
    {
        $this->actingAs($this->adminA, 'sanctum');

        $this->withHeader('X-Active-Annexe-Id', $this->annexeA2->id)
            ->getJson('/api/admin/students')
            ->assertOk();

        $this->withHeader('X-Active-Annexe-Id', $this->annexeB->id)
            ->getJson('/api/admin/students')
            ->assertForbidden();
    }

    public function test_institution_admin_cannot_read_other_institution(): void
    {
        $this->actingAs($this->adminA, 'sanctum');

        $this->getJson("/api/admin/institutions/{$this->annexeA1->institution_id}")->assertOk();
        $this->getJson("/api/admin/institutions/{$this->institutionB->id}")->assertNotFound();
    }

    public function test_public_payment_link_does_not_expose_payplus_transaction_data(): void
    {
        Payment::where('reference', 'REF-MAT-A1')->update([
            'payplus_transaction_id' => 'pp_secret_token',
            'metadata' => json_encode(['payplus_token' => 'pp_secret_token']),
        ]);

        $response = $this->getJson('/api/payment-links/token/tok_MAT-A1')->assertOk();

        $this->assertStringNotContainsString('pp_secret_token', $response->getContent());
    }
}
