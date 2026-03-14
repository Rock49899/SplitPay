<?php

namespace Tests\Feature;

use App\Models\Annexe;
use App\Models\Institution;
use App\Models\PaymentLink;
use App\Models\Student;
use App\Services\PayPlusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PaymentFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_view_payment_link_by_token(): void
    {
        $institution = Institution::create([
            'id' => (string) Str::uuid(),
            'name' => 'School One',
            'email' => 'school.one@example.com',
            'is_active' => true,
        ]);

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'name' => 'Main Campus',
            'is_active' => true,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'annexe_id' => $annexe->id,
            'matricule' => 'MAT-PUBLIC-001',
            'first_name' => 'Jean',
            'last_name' => 'Public',
            'email' => 'jean.public@example.com',
            'status' => 'active',
        ]);

        $link = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_test_public_link_001',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $publicResp = $this->getJson('/api/payment-links/token/' . $link->token);
        $publicResp->assertStatus(200)
            ->assertJsonPath('id', $link->id)
            ->assertJsonPath('token', $link->token);
    }

    public function test_public_checkout_creates_pending_payment_and_returns_payplus_token(): void
    {
        $institution = Institution::create([
            'id' => (string) Str::uuid(),
            'name' => 'School Two',
            'email' => 'school.two@example.com',
            'is_active' => true,
        ]);

        $annexe = Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => $institution->id,
            'name' => 'North Campus',
            'is_active' => true,
        ]);

        $student = Student::create([
            'id' => (string) Str::uuid(),
            'annexe_id' => $annexe->id,
            'matricule' => 'MAT-CHECKOUT-001',
            'first_name' => 'Aline',
            'last_name' => 'Client',
            'email' => 'aline.client@example.com',
            'status' => 'active',
        ]);

        $link = PaymentLink::create([
            'id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'school_year' => '2025-2026',
            'type' => 'tuition',
            'token' => 'tok_test_checkout_link_001',
            'amount' => 500,
            'currency' => 'USD',
            'status' => 'active',
        ]);

        $this->instance(PayPlusService::class, Mockery::mock(PayPlusService::class, function ($mock) {
            $mock->shouldReceive('launchPayment')
                ->once()
                ->andReturn(['token' => 'pp_test_123']);
        }));

        $payload = [
            'payment_link_id' => $link->id,
            'amount' => 500,
            'method' => 'mtn',
            'payer_phone' => '0700000000',
            'payer_first_name' => 'Aline',
            'payer_last_name' => 'Client',
            'payer_email' => 'aline.client@example.com',
        ];

        $resp = $this->postJson('/api/payments/public/checkout', $payload);

        $resp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('payplus_token', 'pp_test_123');

        $reference = $resp->json('reference');
        $this->assertNotEmpty($reference);

        $this->assertDatabaseHas('payments', [
            'reference' => $reference,
            'payment_link_id' => $link->id,
            'student_id' => $student->id,
            'status' => 'pending',
        ]);
    }
}
