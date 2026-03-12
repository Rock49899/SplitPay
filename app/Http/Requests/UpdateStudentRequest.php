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
            'first_name'        => 'sometimes|string|max:100',
            'last_name'         => 'sometimes|string|max:100',
            'email'             => 'sometimes|nullable|email|max:150',
            'phone'             => 'sometimes|nullable|string|max:20',
            'matricule'         => 'sometimes|nullable|string|max:50',
            'specialization_id' => 'sometimes|nullable|integer|exists:specializations,id',
            // statut : conforme à l'énumération en base (active, suspended, graduated)
            'status'            => 'sometimes|string|in:active,suspended,graduated',
            'avatar'            => 'sometimes|file|image|mimes:jpeg,jpg,png,webp|max:2048',
            // Champs d'enrollment (traités à part dans le controller)
            'study_level_id'    => 'sometimes|nullable|integer|exists:study_levels,id',
            'school_year'       => 'sometimes|nullable|string|max:20',
        ];
    }
    
    public function messages()
    {
        return [
            'avatar.file' => 'L\'avatar doit être un fichier.',
            'avatar.image' => 'L\'avatar doit être une image.',
            'avatar.mimes' => 'L\'avatar doit être au format: jpeg, jpg, png ou webp.',
            'avatar.max' => 'L\'avatar ne doit pas dépasser 2 Mo.',
        ];
    }
}
