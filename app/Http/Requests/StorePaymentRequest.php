<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'token' => 'required|string|exists:payment_links,token',
            'installment_id' => 'required|uuid|exists:installments,id',
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string|max:50',
        ];
    }
}
