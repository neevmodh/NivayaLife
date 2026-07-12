<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Doctor extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'name',
        'specialization',
        'hospital_or_clinic_name',
        'phone',
        'notes',
    ];

    public function rules(): array
    {
        return [
            'family_member_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }
}
