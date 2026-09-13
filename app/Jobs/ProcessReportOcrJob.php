<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Report;
use App\Services\Ai\AiClient;
use App\Services\Ocr\OcrExtractor;
use App\Services\Ocr\OcrResolver;
use App\Services\XrayVision\XrayVisionClient;
use App\Support\TempFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads the text out of a freshly uploaded report. PaddleOCR (via
 * ClinicalNlpClient) is tried first — it's consistently more accurate than
 * Tesseract on real-world uploads (phone photos, skewed scans, mixed
 * layouts) — with Tesseract as the fallback when PaddleOCR isn't
 * configured, unreachable, or comes back empty. Never retried automatically
 * — a file that's unreadable once is going to stay unreadable, and
 * retrying just delays the "we couldn't read this" fallback the user needs
 * to see. Kicks off health-metric extraction and the automatic short
 * summary once OCR succeeds; on failure it deliberately stops here, since
 * there's nothing to summarize.
 */
class ProcessReportOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Report $report) {}

    public function handle(OcrExtractor $ocrExtractor, AiClient $ai, XrayVisionClient $xrayVision, OcrResolver $ocrResolver): void
    {
        $report = $this->report->fresh();
        if (! $report) {
            return;
        }

        $report->update(['ocr_status' => 'processing']);

        $aiJob = AiJob::create([
            'report_id' => $report->id,
            'job_type' => 'ocr',
            'status' => 'running',
            'provider' => 'pending',
            'started_at' => now(),
        ]);

        try {
            $mimeType = $report->mime_type ?? '';

            // The upload-time /reports/detect call already ran OcrResolver
            // against this exact file (by hash) to pre-fill the form —
            // reuse that result instead of paying for OCR twice.
            $cached = $report->file_hash ? Cache::get(OcrExtractor::cacheKey($report->file_hash)) : null;

            if ($cached) {
                $text = $cached['text'];
                $usable = $cached['looks_usable'];
                $analysisMethod = $cached['method'] ?? null;
            } else {
                $result = TempFile::fromDisk('local', $report->file_path, fn (string $absolutePath) => $ocrResolver->resolve($absolutePath, $mimeType));
                $text = $result['text'];
                $usable = $result['looks_usable'];
                $analysisMethod = $result['method'];
            }

            if (! $usable) {
                if ($ocrExtractor->isVisionEligible($mimeType) && $ai->hasVisionCapableCredential()) {
                    $this->analyzeWithVision($report, $aiJob, $ocrExtractor, $ai, $xrayVision);

                    return;
                }

                $report->update(['ocr_status' => 'failed']);
                $aiJob->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => 'No readable text could be extracted from this document.',
                ]);

                return;
            }

            $report->update(['ocr_text' => $text, 'ocr_status' => 'completed', 'analysis_method' => $analysisMethod]);
            $aiJob->update(['status' => 'completed', 'completed_at' => now(), 'provider' => $analysisMethod]);

            ExtractHealthMetricsJob::dispatch($report);

            // Structured extraction runs for EVERY type now (ReportSchema
            // defines a shape per type), and it runs *first* — it dispatches
            // GenerateShortSummaryJob itself when it finishes, so the summary
            // can be written from the compact verified structure rather than
            // re-sending the whole OCR text to a paid provider.
            ExtractStructuredDataJob::dispatch($report);
        } catch (Throwable $e) {
            report($e);

            $report->update(['ocr_status' => 'failed']);
            $aiJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    /**
     * Falls back to describing the file visually when OCR found no usable
     * text — the common case for a raw scan image (X-ray/sonography/MRI)
     * that has no embedded text for Tesseract to read at all. Writes
     * straight to ai_summary instead of going through the usual
     * GenerateShortSummaryJob, since there's no ocr_text for that job's
     * text-only prompt to work from. ExtractHealthMetricsJob is
     * deliberately not dispatched here either — its regex extraction
     * assumes literal OCR'd lab values, not a narrative image description.
     */
    private function analyzeWithVision(Report $report, AiJob $aiJob, OcrExtractor $ocrExtractor, AiClient $ai, XrayVisionClient $xrayVision): void
    {
        try {
            [$images, $findings] = TempFile::fromDisk('local', $report->file_path, function (string $absolutePath) use ($report, $ocrExtractor, $xrayVision) {
                $images = $ocrExtractor->visionImages($absolutePath, $report->mime_type ?? '');

                // Real CNN classifier output, when available, for chest X-rays
                // only — this is optional enrichment, never a dependency: any
                // failure here (unconfigured, unreachable, bad response) just
                // means the report falls back to the Gemini-only description
                // that already works today.
                $findings = null;
                if ($report->type === 'xray' && $xrayVision->isConfigured()) {
                    try {
                        $findings = $xrayVision->analyze($absolutePath);
                    } catch (Throwable $e) {
                        report($e);
                    }
                }

                return [$images, $findings];
            });

            $result = $ai->generateWithImage($this->buildVisionPrompt($report, $findings), $images);
            $content = $result['text']."\n\n".GenerateShortSummaryJob::DISCLAIMER;

            $report->update([
                'ocr_status' => 'completed',
                'analysis_method' => 'vision',
                'xray_findings' => $findings,
            ]);
            $report->recordAiResponse('summary', $content, 'en', $aiJob);

            $aiJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'provider' => $result['provider'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ]);
        } catch (Throwable $e) {
            report($e);

            $report->update(['ocr_status' => 'failed']);
            $aiJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Could not read or visually analyze this document: '.Str::limit($e->getMessage(), 450),
            ]);
        }
    }

    /** @param  array<int, array{pathology: string, probability: float}>|null  $findings */
    private function buildVisionPrompt(Report $report, ?array $findings): string
    {
        $findingsBlock = '';

        if ($findings) {
            $top = collect($findings)
                ->take(6)
                ->map(fn ($f) => $f['pathology'].' '.round($f['probability'] * 100).'%')
                ->implode(', ');

            $findingsBlock = <<<BLOCK


            A chest X-ray classifier model estimated these probabilities for common findings: {$top}. Treat this only as supporting context — if the image doesn't actually look like a chest X-ray, ignore it entirely and describe what you actually see.
            BLOCK;
        }

        return <<<PROMPT
        You are looking at an uploaded {$report->typeLabel()} image inside a personal family health-record app. No text could be extracted from it automatically, so describe what you see directly.{$findingsBlock}

        In 2-3 short sentences, state:
        1. What kind of scan/document this appears to be.
        2. What is visibly notable about it, described in plain, cautious language — do not state a definitive diagnosis, only describe visible findings a layperson would want summarized.

        Be brief and plain — this is a teaser someone reads at a glance, not a full explanation. Do not use markdown formatting, headings, or bullet points. Do not include any disclaimer — one is appended separately.
        PROMPT;
    }
}
