<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_updates_payment_status_by_reference()
    {
        if (! class_exists(\App\Models\Payment::class)) {
            $this->markTestSkipped('Payment model missing.');
        }

        // create a payment record
        $payment = \App\Models\Payment::create([
            'id' => (string) Str::uuid(),
            'payment_link_id' => (string) Str::uuid(),
            'amount' => 500,
            'method' => 'card',
            'reference' => 'REFTEST123',
            'status' => 'pending',
            
        ]);

        $payload = [
            'event' => 'payment.updated',
            'data' => [
                'reference' => 'REFTEST123',
                'status' => 'completed',
                'provider' => 'payplus',
            ],
        ];

        $resp = $this->postJson('/api/webhooks/payplus', $payload);
        $resp->assertStatus(200)->assertJson(['message' => 'Webhook processed']);

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
    }
}
