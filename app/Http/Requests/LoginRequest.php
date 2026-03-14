<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        // Tout le monde peut essayer de se connecter
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
        ];
    }

    //message d'erreur
    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit etre valide',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caracteres',
        ];
    }
}
