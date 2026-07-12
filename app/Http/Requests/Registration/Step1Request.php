<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Step1Request extends FormRequest
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
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'blood_group' => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-,Unknown'],
        ];

        if ($viaGoogle) {
            // Email/password are fixed from the verified Google profile, not user input.
            return $rules;
        }

        $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')];
        $rules['password'] = ['required', 'confirmed', 'min:8'];

        return $rules;
    }
}
