<?php

namespace Tests\Feature;

use App\Models\Annexe;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\LevelFee;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Specialization;
use App\Models\StudyLevel;
use App\Models\Student;
use App\Services\PayPlusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Str;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_success_recomputes_enrollment_amount_paid_from_all_tuition_links_of_year(): void
    {
        $institution = Institution::create([
            'id' => (string) Str::uuid(),
            'name' => 'Aggregation School',
            'email' => 'aggregation.school@example.com',
            'is_active' => true,
        ]);

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'name' => 'Aggregation Campus',
            'is_active' => true,
        ]);

        $specialization = Specialization::create([
            'code' => 'INFO',
            'label' => 'Informatique',
        ]);

        $studyLevel = StudyLevel::create([
            'code' => 'L1',
            'label' => 'Licence 1',
        ]);

        $levelFee = LevelFee::create([
            'study_level_id' => $studyLevel->id,
            'specialization_id' => $specialization->id,
            'school_year' => '2025-2026',
            'tuition_amount' => 2000,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'annexe_id' => $annexe->id,
            'matricule' => 'MAT-AGG-001',
            'first_name' => 'Ari',
            'last_name' => 'Aggregate',
            'email' => 'ari.aggregate@example.com',
            'specialization_id' => $specialization->id,
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'level_fee_id' => $levelFee->id,
            'tuition_amount' => 2000,
            'amount_paid' => 0,
            'school_year' => '2025-2026',
            'status' => 'active',
        ]);

        $tuitionLinkA = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_tuition_A_001',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $tuitionLinkB = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_tuition_B_001',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $otherLink = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'other',
            'token' => 'tok_other_001',
            'amount' => 500,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $otherYearTuitionLink = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2024-2025',
            'type' => 'tuition',
            'token' => 'tok_other_year_001',
            'amount' => 700,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => $tuitionLinkA->id,
            'student_id' => $student->id,
            'amount' => 300,
            'method' => 'mtn',
            'reference' => 'REF-AGG-PAID-001',
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $pendingPayment = Payment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => $tuitionLinkB->id,
            'student_id' => $student->id,
            'amount' => 200,
            'method' => 'mtn',
            'reference' => 'REF-AGG-PENDING-001',
            'payplus_transaction_id' => 'pp_txn_agg_001',
            'status' => 'pending',
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => $otherLink->id,
            'student_id' => $student->id,
            'amount' => 90,
            'method' => 'mtn',
            'reference' => 'REF-AGG-OTHER-001',
            'status' => 'success',
            'paid_at' => now(),
        ]);

        Payment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => $otherYearTuitionLink->id,
            'student_id' => $student->id,
            'amount' => 70,
            'method' => 'mtn',
            'reference' => 'REF-AGG-OTHER-YEAR-001',
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $this->mockPayPlusVerify('pp_txn_agg_001', 'completed');

        $resp = $this->postJson('/api/payplus/webhook', [
            'token' => 'pp_txn_agg_001',
            'response_code' => '00',
            'response_text' => 'SUCCESS',
        ]);

        $resp->assertStatus(200)->assertJson(['message' => 'Webhook processed']);

        $pendingPayment->refresh();
        $this->assertSame('success', $pendingPayment->status);

        $enrollment->refresh();
        $this->assertEquals(500.0, (float) $enrollment->amount_paid);
    }

    public function test_webhook_marks_payment_as_failed_with_non_success_response_code(): void
    {
        $institution = Institution::create([
            'id' => (string) Str::uuid(),
            'name' => 'Webhook School',
            'email' => 'webhook.school@example.com',
            'is_active' => true,
        ]);

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'name' => 'Webhook Campus',
            'is_active' => true,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'annexe_id' => $annexe->id,
            'matricule' => 'MAT-WEBHOOK-001',
            'first_name' => 'Paul',
            'last_name' => 'Webhook',
            'email' => 'paul.webhook@example.com',
            'status' => 'active',
        ]);

        $link = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_webhook_link_001',
            'amount' => 500,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $payment = Payment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => $link->id,
            'student_id' => $student->id,
            'amount' => 500,
            'method' => 'mtn',
            'reference' => 'REF-WEBHOOK-001',
            'payplus_transaction_id' => 'pp_txn_001',
            'status' => 'pending',
        ]);

        $payload = [
            'token' => 'pp_txn_001',
            'response_code' => '05',
            'response_text' => 'FAILED',
        ];

        $this->mockPayPlusVerify('pp_txn_001', 'notcompleted');

        $resp = $this->postJson('/api/payplus/webhook', $payload);
        $resp->assertStatus(200)->assertJson(['message' => 'Webhook processed']);

        $payment->refresh();
        $this->assertEquals('failed', $payment->status);

        $this->assertDatabaseHas('notifications', [
            'annexe_id' => $annexe->id,
            'type' => 'payment_failed',
        ]);
    }

    public function test_forged_webhook_cannot_mark_payment_as_paid(): void
    {
        $institution = Institution::create(['name' => 'Forge School', 'email' => 'forge.school@example.com', 'is_active' => true]);
        $annexe = Annexe::create(['institution_id' => $institution->id, 'name' => 'Forge Campus', 'is_active' => true]);
        $student = Student::create([
            'annexe_id' => $annexe->id,
            'matricule' => 'MAT-FORGE-001',
            'first_name' => 'Eve',
            'last_name' => 'Forge',
            'email' => 'eve.forge@example.com',
            'status' => 'active',
        ]);
        $link = PaymentLink::create([
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_forge_001',
            'amount' => 500,
            'currency' => 'XOF',
            'status' => 'active',
        ]);
        $payment = Payment::create([
            'payment_link_id' => $link->id,
            'student_id' => $student->id,
            'amount' => 500,
            'method' => 'mtn',
            'reference' => 'REF-FORGE-001',
            'payplus_transaction_id' => 'pp_txn_forge_001',
            'status' => 'pending',
        ]);

        // L'attaquant prétend "00" mais PayPlus indique que le paiement n'est pas finalisé
        $this->mockPayPlusVerify('pp_txn_forge_001', 'pending');

        $this->postJson('/api/payplus/webhook', [
            'token' => 'pp_txn_forge_001',
            'response_code' => '00',
            'response_text' => 'SUCCESS',
        ])->assertStatus(200)->assertJsonPath('status', 'pending');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('active', $link->fresh()->status);
    }

    public function test_public_checkout_then_webhook_settles_installment_and_link(): void
    {
        $institution = Institution::create(['name' => 'Settle School', 'email' => 'settle.school@example.com', 'is_active' => true]);
        $annexe = Annexe::create(['institution_id' => $institution->id, 'name' => 'Settle Campus', 'is_active' => true]);
        $student = Student::create([
            'annexe_id' => $annexe->id,
            'matricule' => 'MAT-SETTLE-001',
            'first_name' => 'Sam',
            'last_name' => 'Settle',
            'email' => 'sam.settle@example.com',
            'status' => 'active',
        ]);
        $link = PaymentLink::create([
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_settle_001',
            'amount' => 800,
            'currency' => 'XOF',
            'status' => 'active',
        ]);
        $installment = $link->installments()->create([
            'amount' => 800,
            'amount_paid' => 0,
            'status' => 'active',
            'tranche_number' => 1,
        ]);

        $mock = Mockery::mock(PayPlusService::class);
        $mock->shouldReceive('launchPayment')->once()->andReturnUsing(function (Payment $payment) {
            $payment->update(['payplus_transaction_id' => 'pp_txn_settle_001']);
            return ['token' => 'pp_txn_settle_001'];
        });
        $mock->shouldReceive('verify')->with('pp_txn_settle_001')->andReturn((object) ['status' => 'completed']);
        $this->instance(PayPlusService::class, $mock);

        // Montant supérieur au restant dû : refusé
        $this->postJson('/api/payments/public/checkout', [
            'token' => 'tok_settle_001',
            'amount' => 900,
            'method' => 'mtn',
            'payer_phone' => '22900000000',
        ])->assertStatus(422);

        $this->postJson('/api/payments/public/checkout', [
            'token' => 'tok_settle_001',
            'amount' => 800,
            'method' => 'mtn',
            'payer_phone' => '22900000000',
        ])->assertStatus(201);

        $payment = Payment::where('payment_link_id', $link->id)->firstOrFail();
        $this->assertSame($installment->id, $payment->installment_id);

        $this->postJson('/api/payplus/webhook', ['token' => 'pp_txn_settle_001'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertEquals(800.0, (float) $installment->fresh()->amount_paid);
        $this->assertSame('used', $installment->fresh()->status);
        $this->assertSame('used', $link->fresh()->status);
    }

    private function mockPayPlusVerify(string $token, string $status): void
    {
        $this->instance(PayPlusService::class, Mockery::mock(PayPlusService::class, function ($mock) use ($token, $status) {
            $mock->shouldReceive('verify')->with($token)->andReturn((object) ['status' => $status]);
        }));
    }
}
