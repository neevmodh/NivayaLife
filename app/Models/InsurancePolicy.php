<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePolicy extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'report_id',
        'provider_name',
        'policy_number',
        'policy_type',
        'coverage_amount',
        'premium_amount',
        'start_date',
        'expiry_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'coverage_amount' => 'float',
            'premium_amount' => 'float',
            'start_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'provider_name' => ['required', 'string', 'max:255'],
            'policy_number' => ['required', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }
}
