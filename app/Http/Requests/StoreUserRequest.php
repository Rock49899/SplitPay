<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'annexe_id' => 'nullable|uuid|exists:annexes,id',
            'is_active' => 'sometimes|boolean',
            'scope' => 'required|in:institution,annexe',
            'role_id' => 'nullable|uuid|exists:roles,id',
        ];
    }
}
