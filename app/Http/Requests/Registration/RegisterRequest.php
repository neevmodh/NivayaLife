<?php

namespace App\Http\Requests\Registration;

use App\Rules\NoHeaderInjection;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Temporary diagnostic logging — registration is failing for real
     * users with no visible client-side error, and the server logs don't
     * otherwise show which field/rule is being rejected. Remove once the
     * root cause is confirmed.
     */
    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning('Registration validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'password_confirmation']),
        ]);

        parent::failedValidation($validator);
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
