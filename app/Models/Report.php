<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use App\Services\Ai\SummaryFactChecker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Report extends Model
{
    use HasValidation, SoftDeletes;

    public const TYPE_LABELS = [
        'blood_test' => 'Blood test',
        'prescription' => 'Prescription',
        'xray' => 'X-ray',
        'sonography' => 'Sonography/Ultrasound',
        'mri_ct' => 'MRI/CT scan',
        'insurance' => 'Insurance document',
        'bill' => 'Medical bill',
        'ecg' => 'ECG',
        'dental' => 'Dental report',
        'discharge_summary' => 'Discharge summary',
        'pathology' => 'Pathology/Biopsy report',
        'eye_care' => 'Eye care report',
        'other' => 'Medical document',
    ];

    protected $fillable = [
        'family_member_id',
        'uploaded_by_user_id',
        'type',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'file_hash',
        'report_date',
        'hospital_or_clinic_name',
        'doctor_name',
        'uploaded_at',
        'ocr_text',
        'ocr_status',
        'analysis_method',
        'xray_findings',
        'detected_entities',
        'lab_results',
        'structured_data',
        'structured_provider',
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
            'xray_findings' => 'array',
            'detected_entities' => 'array',
            'lab_results' => 'array',
            'structured_data' => 'array',
            'file_size' => 'integer',
            'is_archived' => 'boolean',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'type' => ['required', 'in:blood_test,prescription,xray,sonography,mri_ct,insurance,bill,ecg,dental,discharge_summary,pathology,eye_care,other'],
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

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Medical document';
    }

    /**
     * Records a new AI-generated answer in the permanent history. Only the
     * automatic short summary refreshes the fast-access ai_summary cache —
     * the on-demand "detailed explanation" also uses response_type 'summary'
     * (there's no separate enum value for it) but must NOT overwrite the
     * short teaser shown on dashboard cards, so callers doing a detailed
     * explanation pass $updateSummaryCache: false.
     */
    public function recordAiResponse(
        string $responseType,
        string $content,
        string $language = 'en',
        ?AiJob $aiJob = null,
        bool $updateSummaryCache = true,
    ): AiResponse {
        $response = $this->aiResponses()->create([
            'ai_job_id' => $aiJob?->id,
            'response_type' => $responseType,
            'content' => $content,
            'language' => $language,
        ]);

        if ($responseType === 'summary' && $this->ocr_text) {
            $unverified = SummaryFactChecker::unverifiedNumbers($content, $this->ocr_text);

            if ($unverified !== []) {
                Log::warning('AI summary states numbers not found in source OCR text', [
                    'report_id' => $this->id,
                    'ai_job_id' => $aiJob?->id,
                    'unverified_numbers' => $unverified,
                ]);
            }
        }

        if ($responseType === 'summary' && $updateSummaryCache) {
            $this->update([
                'ai_summary' => $content,
                'ai_summary_language' => $language,
                'ai_summary_generated_at' => now(),
            ]);
        }

        return $response;
    }
}
