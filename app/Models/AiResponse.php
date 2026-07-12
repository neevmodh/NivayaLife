<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permanent history of every AI-generated answer for a report — every summary,
 * every translation, every regeneration. Report::ai_summary stays as a fast
 * "current" cache; this table is the full audit trail behind it.
 */
class AiResponse extends Model
{
    use HasValidation;

    protected $fillable = [
        'report_id',
        'ai_job_id',
        'response_type',
        'content',
        'language',
        'provider',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function rules(): array
    {
        return [
            'report_id' => ['required', 'integer', 'exists:reports,id'],
            'ai_job_id' => ['nullable', 'integer', 'exists:ai_jobs,id'],
            'response_type' => ['required', 'in:summary,translation,entity_extraction'],
            'content' => ['required', 'string'],
        ];
    }

    protected function applyDefaults(): void
    {
        $this->generated_at ??= now();
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function aiJob(): BelongsTo
    {
        return $this->belongsTo(AiJob::class);
    }
}
