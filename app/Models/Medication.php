<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'report_id',
        'medicine_name',
        'dosage',
        'frequency',
        'schedule_times',
        'start_date',
        'end_date',
        'prescribing_doctor',
        'active',
        'reminder_enabled',
    ];

    protected function casts(): array
    {
        return [
            'schedule_times' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'boolean',
            'reminder_enabled' => 'boolean',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'medicine_name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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

    public function medicationLogs(): HasMany
    {
        return $this->hasMany(MedicationLog::class);
    }
}
