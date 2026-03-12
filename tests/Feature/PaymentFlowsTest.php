<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Models\User;

class PaymentFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_payment_link_and_public_can_view_and_pay()
    {
        if (! class_exists(\App\Models\PaymentLink::class) || ! class_exists(\App\Models\Annexe::class)) {
            $this->markTestSkipped('PaymentLink or Annexe model missing.');
        }

        // create admin
        $admin = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin@local.test',
            'password' => bcrypt('secret'),
            'scope' => 'institution',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum');

        // create annexe
        $annexe = \App\Models\Annexe::create([
            'id' => (string) Str::uuid(),
            'institution_id' => \App\Models\Institution::factory()->create()->id ?? (string) Str::uuid(),
            'name' => 'Main Campus',
            'is_active' => true,
        ]);

        // admin create payment link
        $payload = [
            'title' => 'Tuition fee',
            'description' => 'Term 1',
            'amount' => 1000,
            'currency' => 'USD',
            'annexe_id' => $annexe->id,
        ];

        $resp = $this->postJson('/api/admin/payment-links', $payload);
        $resp->assertStatus(201);

        $link = $resp->json('payment_link') ?? $resp->json('paymentLink') ?? $resp->json('payment_link', []);

        // ensure we have id or public_token
        $this->assertTrue(!empty($link['id'] ?? null) || !empty($link['public_token'] ?? null));

        $token = $link['public_token'] ?? null;
        if (! $token && ! empty($link['id'])) {
            // try to fetch token from record
            $record = \App\Models\PaymentLink::find($link['id']);
            $token = $record->public_token ?? null;
        }

        $this->assertNotEmpty($token);

        // public view by token
        $publicResp = $this->getJson("/api/payment-links/{$token}");
        $publicResp->assertStatus(200)->assertJsonFragment(['title' => 'Tuition fee']);

        // public create payment
        if (! class_exists(\App\Models\Payment::class)) {
            $this->markTestSkipped('Payment model missing.');
        }

        $payPayload = [
            'payment_link_id' => $link['id'] ?? $record->id,
            'amount' => 1000,
            'method' => 'card',
        ];

        $payResp = $this->postJson('/api/payments/public', $payPayload);
        $payResp->assertStatus(201)->assertJsonStructure(['message','payment']);
    }
}
