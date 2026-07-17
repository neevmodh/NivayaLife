<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Report;
use App\Services\Ocr\OcrExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Runs Tesseract (via OcrExtractor) against a freshly uploaded report. Never
 * retried automatically — a file that's unreadable once is going to stay
 * unreadable, and retrying just delays the "we couldn't read this" fallback
 * the user needs to see. Kicks off health-metric extraction and the
 * automatic short summary once OCR succeeds; on failure it deliberately
 * stops here, since there's nothing to summarize.
 */
class ProcessReportOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Report $report) {}

    public function handle(OcrExtractor $ocrExtractor): void
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
            'provider' => 'tesseract',
            'started_at' => now(),
        ]);

        try {
            // The upload-time /reports/detect call already ran OCR against
            // this exact file (by hash) to pre-fill the form — reuse it
            // instead of paying for Tesseract twice.
            $cached = $report->file_hash ? Cache::get(OcrExtractor::cacheKey($report->file_hash)) : null;

            if ($cached) {
                $text = $cached['text'];
                $usable = $cached['looks_usable'];
            } else {
                $absolutePath = Storage::disk('local')->path($report->file_path);
                $text = $ocrExtractor->extract($absolutePath, $report->mime_type ?? '');
                $usable = $ocrExtractor->looksUsable($text);
            }

            if (! $usable) {
                $report->update(['ocr_status' => 'failed']);
                $aiJob->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_message' => 'No readable text could be extracted from this document.',
                ]);

                return;
            }

            $report->update(['ocr_text' => $text, 'ocr_status' => 'completed']);
            $aiJob->update(['status' => 'completed', 'completed_at' => now()]);

            ExtractHealthMetricsJob::dispatch($report);
            GenerateShortSummaryJob::dispatch($report);
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
}
