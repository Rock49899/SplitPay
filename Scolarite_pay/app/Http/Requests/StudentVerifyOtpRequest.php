<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentVerifyOtpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'matricule' => 'required|string|max:50',
            'otp'       => 'required|string|size:6',
        ];
    }
}
