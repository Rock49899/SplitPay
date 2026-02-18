<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    // règles simples et permissives pour mise à jour partielle
    public function rules()
    {
        return [
            'first_name'   => 'sometimes|required|string|max:100',
            'last_name'    => 'sometimes|required|string|max:100',
            'email'        => 'sometimes|nullable|email|max:150',
            'phone'        => 'sometimes|nullable|string|max:20',
            'matricule'      => 'sometimes|nullable|string|max:50',
            'class'        => 'sometimes|nullable|string|max:100',
            'school_year'  => 'sometimes|nullable|string|max:20',
            // statut : conforme à l'énumération en base (active, suspended, graduated)
            'status'       => 'sometimes|string|in:active,suspended,graduated',
        ];
    }
}
