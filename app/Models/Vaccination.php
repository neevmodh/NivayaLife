<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'vaccine_name',
        'dose_number',
        'date_administered',
        'next_due_date',
        'last_reminded_at',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'date_administered' => 'date',
            'next_due_date' => 'date',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'vaccine_name' => ['required', 'string', 'max:255'],
            'dose_number' => ['required', 'integer', 'min:1'],
            'date_administered' => ['required', 'date', 'before_or_equal:tomorrow'],
            'next_due_date' => ['nullable', 'date', 'after:date_administered'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function isOverdue(): bool
    {
        return $this->next_due_date !== null && $this->next_due_date->toDateString() < now()->toDateString();
    }

    public function isDueSoon(): bool
    {
        return $this->next_due_date !== null
            && ! $this->isOverdue()
            && $this->next_due_date->toDateString() <= now()->addDays(7)->toDateString();
    }
}
