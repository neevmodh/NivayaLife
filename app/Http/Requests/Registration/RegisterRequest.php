<?php

namespace App\Http\Requests\Registration;

use App\Rules\NoHeaderInjection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $viaGoogle = session('wizard.google') !== null;

        $rules = [
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'digits:10'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            // Optional at signup — nothing in the app gates a feature on
            // these, they're compliance/audit logging only, so a user isn't
            // blocked from creating an account just to check three boxes.
            'consent_account_creation' => ['nullable', 'boolean'],
            'consent_upload' => ['nullable', 'boolean'],
            'consent_ai_processing' => ['nullable', 'boolean'],
        ];

        if ($viaGoogle) {
            // Email/password are fixed from the verified Google profile, not user input.
            return $rules;
        }

        $rules['email'] = ['required', 'string', 'email', 'max:255', new NoHeaderInjection, Rule::unique('users', 'email')];
        $rules['password'] = ['required', 'confirmed', 'min:8'];

        return $rules;
    }
}
