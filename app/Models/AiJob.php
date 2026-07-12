<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiJob extends Model
{
    use HasValidation;

    protected $fillable = [
        'report_id',
        'job_type',
        'status',
        'provider',
        'input_tokens',
        'output_tokens',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
        ];
    }

    public function rules(): array
    {
        return [
            'report_id' => ['required', 'integer', 'exists:reports,id'],
            'job_type' => ['required', 'in:ocr,summary,translation,entity_extraction'],
            'status' => ['required', 'in:queued,running,completed,failed'],
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function totalTokens(): int
    {
        return ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
    }

    public function aiResponses(): HasMany
    {
        return $this->hasMany(AiResponse::class);
    }
}
