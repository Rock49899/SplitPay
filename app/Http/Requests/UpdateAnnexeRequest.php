<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnexeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'           => 'sometimes|required|string|max:255',
            'address'        => 'sometimes|nullable|string|max:1000',
            'city'           => 'sometimes|nullable|string|max:255',
            'annexe_details' => 'sometimes|nullable|array', // Accepter un objet JSON
            'is_active'      => 'sometimes|boolean',
        ];
    }
}
