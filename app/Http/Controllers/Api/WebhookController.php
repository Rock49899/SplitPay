<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\WebhookRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;

class WebhookController extends Controller
{
    // POST /api/webhooks/payplus
    public function handle(WebhookRequest $request)
    {
        $payload = $request->validated();
        Log::info('Webhook received', $payload);

        $data = $payload['data'] ?? [];
        if (!empty($data['reference'])) {
            $payment = Payment::where('reference', $data['reference'])->first();
            if ($payment) {
                $payment->update(['status' => $data['status'] ?? 'unknown', 'metadata' => array_merge((array)$payment->metadata ?: [], $data)]);
            }
        }

        return response()->json(['message'=>'Webhook processed'], 200);
    }
}
