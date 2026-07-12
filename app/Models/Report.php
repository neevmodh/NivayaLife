<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasValidation, SoftDeletes;

    protected $fillable = [
        'family_member_id',
        'uploaded_by_user_id',
        'type',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'report_date',
        'hospital_or_clinic_name',
        'doctor_name',
        'uploaded_at',
        'ocr_text',
        'ocr_status',
        'ai_summary',
        'ai_summary_language',
        'ai_summary_generated_at',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'uploaded_at' => 'datetime',
            'ai_summary_generated_at' => 'datetime',
            'file_size' => 'integer',
            'is_archived' => 'boolean',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'type' => ['required', 'in:blood_test,prescription,xray,mri_ct,insurance,bill,ecg,other'],
            'file_path' => ['required', 'string'],
            'original_filename' => ['required', 'string', 'max:255'],
            'ocr_status' => ['required', 'in:pending,processing,completed,failed'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function healthMetrics(): HasMany
    {
        return $this->hasMany(HealthMetric::class);
    }

    public function aiJobs(): HasMany
    {
        return $this->hasMany(AiJob::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function aiResponses(): HasMany
    {
        return $this->hasMany(AiResponse::class);
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    /**
     * Records a new AI-generated answer in the permanent history, and refreshes
     * the fast-access "current" summary cache on the report itself when the
     * response is the primary summary type.
     */
    public function recordAiResponse(string $responseType, string $content, string $language = 'en', ?AiJob $aiJob = null): AiResponse
    {
        $response = $this->aiResponses()->create([
            'ai_job_id' => $aiJob?->id,
            'response_type' => $responseType,
            'content' => $content,
            'language' => $language,
        ]);

        if ($responseType === 'summary') {
            $this->update([
                'ai_summary' => $content,
                'ai_summary_language' => $language,
                'ai_summary_generated_at' => now(),
            ]);
        }

        return $response;
    }
}
