<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationLog extends Model
{
    use HasValidation;

    protected $fillable = [
        'medication_id',
        'scheduled_at',
        'taken_at',
        'reminded_at',
        'escalation_sent_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'taken_at' => 'datetime',
            'reminded_at' => 'datetime',
            'escalation_sent_at' => 'datetime',
        ];
    }

    public function rules(): array
    {
        return [
            'medication_id' => ['required', 'integer', 'exists:medications,id'],
            'scheduled_at' => ['required', 'date'],
            'status' => ['required', 'in:pending,taken,missed,skipped'],
        ];
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
