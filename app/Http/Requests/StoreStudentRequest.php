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
            'annexe_id'      => 'nullable|uuid|exists:annexes,id',
            'matricule'      => 'required|string|max:50|unique:students,matricule',
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'nullable|email|max:150',
            'phone'          => 'nullable|string|max:20',
            'class'          => 'nullable|string|max:100',
            'school_year'    => 'required|string|max:20',
            'tuition_amount' => 'required|numeric|min:0',
        ];
    }
}
