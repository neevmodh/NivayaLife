<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class Step5Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'digits:10'],
            'emergency_contact_relation' => ['required', 'string', 'max:255'],
            'consent_account_creation' => ['accepted'],
            'consent_upload' => ['accepted'],
            'consent_ai_processing' => ['accepted'],
        ];
    }
}
