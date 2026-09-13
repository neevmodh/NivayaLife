<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Report;
use App\Services\Ai\AiClient;
use App\Services\ClinicalNlp\ClinicalNlpClient;
use App\Services\Ocr\OcrExtractor;
use App\Services\Reports\OcrCleaner;
use App\Support\TempFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * The one automatic AI call in the whole pipeline — deliberately cheap (2-3
 * sentences) since it runs unattended for every uploaded report. Every
 * other AI action (detailed explanation, translation) is user-initiated.
 */
class GenerateShortSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public const DISCLAIMER = 'This is not medical advice. Please consult a qualified doctor for diagnosis and treatment.';

    private const MAX_OCR_CHARS = 8000;

    public function __construct(public Report $report) {}

    public function handle(AiClient $ai, ClinicalNlpClient $clinicalNlp, OcrExtractor $ocrExtractor): void
    {
        $report = $this->report->fresh();

        if (! $report || $report->ocr_status !== 'completed' || ! $report->ocr_text || $report->ai_summary) {
            return;
        }

        if (! $ai->hasAvailableCredential()) {
            // No AI provider configured at all — retrying won't help until
            // that changes, so don't keep re-queueing.
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
            $result = $this->generateSummary($report, $ai, $ocrExtractor);
            $content = $result['text']."\n\n".self::DISCLAIMER;

            $report->recordAiResponse('summary', $content, 'en', $aiJob);

            $aiJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'provider' => $result['provider'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ]);

            $this->detectEntities($report, $clinicalNlp);

            EmbedRecordJob::dispatch(
                $report->family_member_id,
                'report',
                $report->id,
                "{$report->typeLabel()} ({$report->report_date?->toDateString()}): {$content}",
            );
        } catch (Throwable $e) {
            report($e);

            $aiJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    /**
     * Prescriptions are overwhelmingly the report type most likely to
     * contain a doctor's handwriting — and OCR (Tesseract or PaddleOCR)
     * routinely produces just enough clean, machine-printed text (a
     * clinic's letterhead, typed patient details) to pass looksUsable()
     * while silently losing or mangling the actual handwritten
     * instructions, which is exactly the part a user needs read correctly.
     * Neither OCR engine is built for handwriting at all — Gemini's vision
     * model reads it noticeably better from the image directly, using the
     * OCR text only as a cross-check rather than the source of truth. Any
     * failure loading the image (missing file, unreadable format) falls
     * back to the existing text-only prompt rather than failing the job.
     *
     * @return array{text: string, provider: string, input_tokens: ?int, output_tokens: ?int}
     */
    private function generateSummary(Report $report, AiClient $ai, OcrExtractor $ocrExtractor): array
    {
        if ($this->needsVision($report, $ocrExtractor)) {
            try {
                $images = TempFile::fromDisk('local', $report->file_path, fn (string $absolutePath) => $ocrExtractor->visionImages($absolutePath, $report->mime_type ?? ''));

                return $ai->generateWithImage($this->buildVisionPrompt($report), $images);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $ai->generate($this->buildPrompt($report));
    }

    /**
     * Prescriptions always go to vision — they are handwritten by default, and
     * OCR routinely returns just enough clean letterhead text to pass
     * looksUsable() while losing the actual handwritten instructions.
     *
     * Beyond that, ANY type whose OCR text came back unusable is very likely a
     * handwritten or photographed note, so it gets the same treatment rather
     * than being summarised from near-empty text. This is what makes
     * handwriting work across all report types instead of prescriptions only.
     */
    private function needsVision(Report $report, OcrExtractor $ocrExtractor): bool
    {
        if (! $ocrExtractor->isVisionEligible($report->mime_type ?? '')) {
            return false;
        }

        return $report->type === 'prescription'
            || ! $ocrExtractor->looksUsable((string) $report->ocr_text);
    }

    /**
     * Biomedical entity recognition (drug/diagnosis mentions) via the
     * optional clinical-nlp-service — display-only enrichment, never a
     * reason to fail this job: any error here is swallowed after logging.
     */
    private function detectEntities(Report $report, ClinicalNlpClient $clinicalNlp): void
    {
        if (! $clinicalNlp->isConfigured()) {
            return;
        }

        try {
            $entities = $clinicalNlp->extractEntities($report->ocr_text);
            $report->update(['detected_entities' => $entities]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Prefers the structured data ExtractStructuredDataJob already produced.
     *
     * This is where the token saving lands: a compact verified structure is a
     * fraction of the 8000 OCR characters this used to send, and the flags in
     * it have already been recomputed numerically by LabFlag — so the model is
     * describing checked data rather than re-deriving it from noisy text and
     * potentially getting the arithmetic wrong.
     *
     * Falls back to cleaned OCR text when extraction produced nothing.
     */
    private function buildPrompt(Report $report): string
    {
        $structured = $report->structured_data;

        $source = $structured
            ? "ALREADY-EXTRACTED, ALREADY-VERIFIED DATA (JSON):\n".json_encode($structured, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : "OCR TEXT (may contain minor OCR errors):\n".Str::limit(OcrCleaner::clean($report->ocr_text), self::MAX_OCR_CHARS, '');

        $provenance = $structured
            ? 'Every value below was extracted and range-checked already — describe it, do not recalculate it, and never mention a value that is not present below.'
            : 'This is raw extracted text and may contain errors.';

        return <<<PROMPT
        You are generating a brief glance-level teaser for a {$report->typeLabel()} inside a personal family health-record app.

        {$provenance}

        In 2-3 short sentences, state:
        1. What kind of report this is.
        2. The single most notable thing in it — either a confirmation that values are in the normal range, or the one most important flagged/abnormal value.

        Be brief and plain — this is a teaser someone reads at a glance, not a full explanation. Do not use markdown formatting, headings, or bullet points. Do not include any disclaimer — one is appended separately.

        {$source}
        PROMPT;
    }

    /**
     * Unlike buildPrompt(), this is paired with the actual report image —
     * the model can read the handwriting directly rather than relying
     * solely on what OCR managed to extract.
     */
    private function buildVisionPrompt(Report $report): string
    {
        $ocrText = Str::limit($report->ocr_text, self::MAX_OCR_CHARS, '');

        return <<<PROMPT
        You are looking at an uploaded prescription image inside a personal family health-record app. Read the image directly, including any handwritten portions — a doctor's handwriting is often illegible to automated text extraction, so do not rely only on the OCR text below; use it only as a possibly-incomplete cross-check, and prefer what you can actually see in the image whenever the two disagree.

        In 2-3 short sentences, state:
        1. What kind of report this is.
        2. The medicines and instructions you can make out — say plainly if a portion of the handwriting is illegible rather than guessing with false confidence.

        Be brief and plain — this is a teaser someone reads at a glance, not a full explanation. Do not use markdown formatting, headings, or bullet points. Do not include any disclaimer — one is appended separately.

        OCR TEXT (may be incomplete — the handwriting itself may not have been captured):
        {$ocrText}
        PROMPT;
    }
}
