<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstitutionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $institutionId = $this->route('id') ?? $this->route('institution');

        return [
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|max:150|unique:institutions,email,'.$institutionId.',id',
            'phone'     => 'nullable|string|max:30',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
