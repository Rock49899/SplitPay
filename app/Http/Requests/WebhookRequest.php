<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'event' => 'required|string',
            'data' => 'required|array',
            'signature' => 'sometimes|string',
        ];
    }
}
