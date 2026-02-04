<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentLoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'matricule' => 'required|string|max:50',
        ];
    }
}
