<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_email' => ['nullable', 'email', 'max:255'],
            'institution_phone' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'], // max 2MB
            
            'annexe_name' => ['nullable', 'string', 'max:255'],
            'annexe_details' => ['nullable', 'string'], // JSON string

            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:6'],
        ];
    } 

    public function messages(): array
    {
        return [
            'institution_name.required' => 'Le nom de l\'institution est obligatoire.',
            'owner_name.required' => 'Le nom du responsable est obligatoire.',
            'owner_email.required' => 'L\'email du responsable est obligatoire.',
            'owner_email.email' => 'L\'email du responsable doit être valide.',
            'owner_email.unique' => 'Un compte avec cet e-mail existe déjà.',
            'owner_password.required' => 'Le mot de passe est obligatoire.',
            'owner_password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ];
    }
}
