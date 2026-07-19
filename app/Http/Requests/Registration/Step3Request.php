<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class Step3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The whole address is optional at signup — it can be filled in later
     * from the profile page. The location-select widget always defaults its
     * hidden country field to India even when the user never touched it, so
     * "was an address actually given" has to key off address_line1 (a field
     * with no default) rather than country — otherwise pincode would end up
     * required on every skip.
     */
    public function rules(): array
    {
        $pincodeRule = match (true) {
            filled($this->input('address_line1')) && $this->input('country') === 'India' => ['required', 'digits:6'],
            filled($this->input('address_line1')) => ['required', 'string', 'max:12'],
            default => ['nullable', 'string', 'max:12'],
        };

        return [
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'pincode' => $pincodeRule,
        ];
    }
}
