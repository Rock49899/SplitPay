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
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $resp = $this->postJson('/api/payplus/webhook', $payload);
        $resp->assertStatus(200)->assertJson(['message' => 'Webhook processed']);

        $payment->refresh();
        $this->assertEquals('failed', $payment->status);

        $this->assertDatabaseHas('notifications', [
            'annexe_id' => $annexe->id,
            'type' => 'payment_failed',
        ]);
    }
}
