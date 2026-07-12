<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthMetric extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'report_id',
        'metric_type',
        'value',
        'unit',
        'recorded_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'recorded_date' => 'date',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'metric_type' => ['required', 'in:blood_pressure_systolic,blood_pressure_diastolic,blood_sugar_fasting,blood_sugar_pp,hba1c,cholesterol_total,cholesterol_ldl,cholesterol_hdl,hemoglobin,other'],
            'value' => ['required', 'numeric'],
            'recorded_date' => ['required', 'date', 'before_or_equal:tomorrow'],
            'source' => ['required', 'in:manual,ocr_extracted,ai_extracted'],
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
}
