<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'annexe_id'         => 'nullable|uuid|exists:annexes,id',
            'matricule'         => 'required|string|max:50|unique:students,matricule',
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'nullable|email|max:150',
            'phone'             => 'nullable|string|max:20',
            'specialization_id' => 'nullable|exists:specializations,id',
            'avatar'            => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            // Champs d'enrollment (ne vont PAS sur la table students)
            'study_level_id'    => 'nullable|exists:study_levels,id',
            'school_year'       => 'required|string|max:20',
        ];
    }
}
