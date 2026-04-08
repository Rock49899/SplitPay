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
            'password' => 'sometimes|nullable|string|min:8',
            'annexe_id' => 'nullable|uuid|exists:annexes,id',
            'is_active' => 'sometimes|boolean',
            'scope' => 'sometimes|nullable|in:platform,institution,annexe',
            'role_id' => 'nullable|uuid|exists:roles,id',
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
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
