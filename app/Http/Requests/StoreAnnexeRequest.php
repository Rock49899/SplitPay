<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnexeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // institution_id peut être omis : le serveur mettra la valeur par défaut
            'institution_id' => 'sometimes|nullable|uuid|exists:institutions,id',
            'name'           => 'required|string|max:255',
            'address'        => 'sometimes|nullable|string|max:1000',
            'city'           => 'sometimes|nullable|string|max:255',
            // champ envoyé par le client, sera mappé dans le controller vers la colonne 'details'
            'annexe_details' => 'sometimes|nullable|string',
            'is_active'      => 'sometimes|boolean',
        ];
    }
}
