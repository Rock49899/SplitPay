<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallmentRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'payment_link_id' => 'required|uuid|exists:payment_links,id',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
        ];
    }
}
