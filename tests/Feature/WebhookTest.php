<?php

namespace Tests\Feature;

use App\Models\Annexe;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

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
