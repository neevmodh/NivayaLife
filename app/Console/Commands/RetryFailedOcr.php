<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReportOcrJob;
use App\Models\Report;
use Illuminate\Console\Command;

/**
 * Re-queues OCR for every report currently stuck on ocr_status=failed —
 * meant to be run once after a fix to the extraction pipeline itself (e.g.
 * the eng+hin+guj language-priority regression), not as a routine retry:
 * a report that's still genuinely unreadable (blank photo, corrupted file)
 * will just land back on failed, which is the correct outcome, not a bug.
 */
class RetryFailedOcr extends Command
{
    protected $signature = 'reports:retry-failed-ocr';

    protected $description = 'Re-queue OCR for every report currently marked ocr_status=failed.';

    public function handle(): int
    {
        // Report's HasValidation trait re-validates every required column
        // on save, so a partial select (e.g. ->get(['id'])) leaves those
        // columns unloaded and validation fails as if they were empty.
        $reports = Report::where('ocr_status', 'failed')->get();

        if ($reports->isEmpty()) {
            $this->info('No failed reports to retry.');

            return self::SUCCESS;
        }

        foreach ($reports as $report) {
            $report->update(['ocr_status' => 'pending']);
            ProcessReportOcrJob::dispatch($report);
        }

        $this->info("Re-queued OCR for {$reports->count()} report(s). Check back in a few minutes — the queue workers process these in the background.");

        return self::SUCCESS;
    }
}
