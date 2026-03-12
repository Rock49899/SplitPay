<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentLinkRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'student_id' => 'required|uuid|exists:students,id',
            'amount'     => 'required|numeric|min:0.01',
            'type'       => 'sometimes|string',
            'description'=> 'sometimes|nullable|string',
            'due_date'   => 'sometimes|nullable|date',
            'expire_at'  => 'sometimes|nullable|date',
            // devise choisie par l'admin (ex: USD, EUR, XOF)
            'currency'   => 'sometimes|string|max:10',
        ];
    }
}
