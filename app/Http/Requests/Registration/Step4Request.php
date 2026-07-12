<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class Step4Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'height_cm' => ['required', 'numeric', 'min:30', 'max:280'],
            'weight_kg' => ['required', 'numeric', 'min:2', 'max:400'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'medicines' => ['nullable', 'array'],
            'medicines.*' => ['string', 'max:255'],
        ];
    }
}
