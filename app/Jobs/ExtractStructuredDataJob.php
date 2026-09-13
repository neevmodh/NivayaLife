<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Report;
use App\Services\Ocr\OcrExtractor;
use App\Services\Reports\ReportSchema;
use App\Services\Reports\StructuredExtractor;
use App\Support\TempFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pulls the structured shape out of a report, for every type — replacing the
 * old blood-test-only ExtractLabResultsJob.
 *
 * Runs *before* the summary so GenerateShortSummaryJob can be grounded in
 * verified structured data instead of re-sending the whole OCR text. That
 * ordering is the token saving: extraction goes to the free local model, and
 * the paid provider only ever sees the compact extracted result.
 *
 * Display-and-grounding enrichment only: any failure here leaves
 * structured_data null and the summary falls back to cleaned OCR text.
 */
class ExtractStructuredDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /** Extraction can hit Ollama then fall back through the whole Gemini/Groq chain. */
    public int $timeout = 240;

    public array $backoff = [10, 30];

    public function __construct(public Report $report) {}

    public function handle(StructuredExtractor $extractor, OcrExtractor $ocrExtractor): void
    {
        $report = $this->report->fresh();

        if (! $report || ! $report->ocr_text || $report->structured_data !== null) {
            return;
        }

        $aiJob = AiJob::create([
            'report_id' => $report->id,
            'job_type' => 'entity_extraction',
            'status' => 'running',
            'provider' => 'pending',
            'started_at' => now(),
        ]);

        try {
            $result = $this->extractFor($report, $extractor, $ocrExtractor);

            $update = [
                'structured_data' => $result['data'],
                'structured_provider' => $result['provider'],
            ];

            // Result-table types keep populating lab_results too: the report
            // page and the mobile API already read it, and moving those
            // readers over is a separate change from shipping extraction for
            // the other twelve types.
            if (ReportSchema::hasMeasuredResults($report->type)) {
                $update['lab_results'] = $result['data']['results'] ?? [];
            }

            $report->update($update);

            $aiJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'provider' => $result['provider'],
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
        } finally {
            // The summary runs either way — with structure if extraction
            // worked, from cleaned OCR text if it didn't.
            GenerateShortSummaryJob::dispatch($report);
        }
    }

    /**
     * Handwriting goes to the vision model, everything else to the cheap local
     * text path.
     *
     * The summary job has always read handwritten prescriptions from the image;
     * extraction did not, and inherited OCR's mistakes — measured on a real
     * handwritten prescription, "30 days" became "80 days" and a 500mg dose
     * vanished. Both halves now make the same routing decision, from the same
     * method, so a report is never summarised from the image and structured
     * from garbled text.
     *
     * Any failure loading the image falls back to the text path rather than
     * losing the extraction entirely.
     *
     * @return array{data: array, provider: string, input_tokens: ?int, output_tokens: ?int}
     */
    private function extractFor(Report $report, StructuredExtractor $extractor, OcrExtractor $ocrExtractor): array
    {
        if ($ocrExtractor->needsVisualReading($report->type, $report->mime_type, $report->ocr_text)) {
            try {
                $images = TempFile::fromDisk('local', $report->file_path, fn (string $absolutePath) => $ocrExtractor->visionImages($absolutePath, $report->mime_type ?? ''));

                return $extractor->extractFromImages($report->type, $images, $report->ocr_text);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $extractor->extract($report->type, $report->ocr_text);
    }
}
