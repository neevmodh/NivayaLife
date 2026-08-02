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
            // No 'boolean' type rule: a checked HTML checkbox with no
            // explicit value submits the literal string "on", which the
            // strict 'boolean' rule rejects — the controller only does a
            // truthy check on this value, so any non-empty string is fine.
            'consent_account_creation' => ['nullable'],
            'consent_upload' => ['nullable'],
            'consent_ai_processing' => ['nullable'],
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
