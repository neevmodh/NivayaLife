<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BmiLog extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'height_cm',
        'weight_kg',
        'bmi_value',
        'bmi_category',
        'recorded_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'height_cm' => 'float',
            'weight_kg' => 'float',
            'bmi_value' => 'float',
            'recorded_date' => 'date',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'height_cm' => ['required', 'numeric', 'min:30', 'max:280'],
            'weight_kg' => ['required', 'numeric', 'min:2', 'max:400'],
            'recorded_date' => ['required', 'date', 'before_or_equal:tomorrow'],
            'source' => ['required', 'in:manual,report_extracted'],
        ];
    }

    /**
     * bmi_value and bmi_category are stored (not just computed on read) so trend
     * queries don't need to recalculate every row. Recomputed automatically here
     * whenever height or weight changes.
     */
    protected static function booted(): void
    {
        static::saving(function (BmiLog $log) {
            if ($log->isDirty('height_cm') || $log->isDirty('weight_kg')) {
                $heightM = $log->height_cm / 100;
                $log->bmi_value = round($log->weight_kg / ($heightM * $heightM), 1);
                $log->bmi_category = self::categoryFor($log->bmi_value);
            }
        });
    }

    public static function categoryFor(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'underweight',
            $bmi < 25 => 'normal',
            $bmi < 30 => 'overweight',
            default => 'obese',
        };
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }
}
