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
            'phone' => 'sometimes|nullable|string|max:50',
            'annexe_id' => 'nullable|uuid|exists:annexes,id',
            'is_active' => 'sometimes|boolean',
            'avatar' => 'sometimes|nullable|file|image|mimes:jpeg,jpg,png,webp|max:2048',
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
