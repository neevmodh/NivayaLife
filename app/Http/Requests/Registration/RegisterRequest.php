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
            'country_code' => ['required', 'regex:/^\+[1-9]\d{0,3}$/'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            // Same bounds as FamilyMember::rules()/ProfileController::updateHealth() —
            // required here (unlike a later profile edit) since the signup
            // form's BMI gauge always renders both fields.
            'height_cm' => ['required', 'numeric', 'min:30', 'max:280'],
            'weight_kg' => ['required', 'numeric', 'min:2', 'max:400'],
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
