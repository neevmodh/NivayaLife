<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Report;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiQuota;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * The one automatic Gemini call in the whole pipeline — deliberately cheap
 * (2-3 sentences) since it runs unattended for every uploaded report. Every
 * other AI action (detailed explanation, translation) is user-initiated, to
 * protect the free-tier daily quota.
 */
class GenerateShortSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public const DISCLAIMER = 'This is not medical advice. Please consult a qualified doctor for diagnosis and treatment.';

    private const MAX_OCR_CHARS = 8000;

    public function __construct(public Report $report) {}

    public function handle(GeminiClient $gemini, GeminiQuota $quota): void
    {
        $report = $this->report->fresh();

        if (! $report || $report->ocr_status !== 'completed' || ! $report->ocr_text || $report->ai_summary) {
            return;
        }

        if ($quota->isNearLimit()) {
            // Don't fail or spend the little quota that's left — try again
            // once the daily counter has had a chance to move (or reset).
            $this->release(min($quota->secondsUntilReset(), 1800));

            return;
        }

        $aiJob = AiJob::create([
            'report_id' => $report->id,
            'job_type' => 'summary',
            'status' => 'running',
            'provider' => 'gemini',
            'started_at' => now(),
        ]);

        try {
            $result = $gemini->generate($this->buildPrompt($report));
            $content = $result['text']."\n\n".self::DISCLAIMER;

            $report->recordAiResponse('summary', $content, 'en', $aiJob);

            $aiJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ]);
        } catch (Throwable $e) {
            report($e);

            $aiJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    private function buildPrompt(Report $report): string
    {
        $ocrText = Str::limit($report->ocr_text, self::MAX_OCR_CHARS, '');

        return <<<PROMPT
        You are generating a brief glance-level teaser for a {$report->typeLabel()} inside a personal family health-record app. Below is text extracted via OCR from the document — it may contain minor OCR errors.

        In 2-3 short sentences, state:
        1. What kind of report this is.
        2. The single most notable thing in it — either a confirmation that values are in the normal range, or the one most important flagged/abnormal value.

        Be brief and plain — this is a teaser someone reads at a glance, not a full explanation. Do not use markdown formatting, headings, or bullet points. Do not include any disclaimer — one is appended separately.

        OCR TEXT:
        {$ocrText}
        PROMPT;
    }
}
