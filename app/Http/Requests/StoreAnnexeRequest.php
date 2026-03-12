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
            // Accepter un objet JSON (array) pour les détails structurés
            'annexe_details' => 'sometimes|nullable|array',
            'is_active'      => 'sometimes|boolean',
        ];
    }
}
