<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnexeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'institution_id' => 'required|uuid|exists:institutions,id',
            'name'           => 'required|string|max:255',
            'is_active'      => 'sometimes|boolean',
        ];
    }
}
