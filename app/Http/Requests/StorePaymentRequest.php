<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'payment_link_id' => 'required|uuid|exists:payment_links,id',
            'installment_id' => 'nullable|uuid|exists:installments,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'metadata' => 'nullable|array',
        ];
    }
}
