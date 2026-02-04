<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('id') ?? $this->route('user');
        return [
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|nullable|string|min:8',
            'annexe_id' => 'nullable|uuid|exists:annexes,id',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
