<?php

namespace App\Jobs;

use App\Models\HealthMetric;
use App\Models\Report;
use App\Services\Reports\HealthMetricExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Runs right after OCR succeeds rather than waiting for the on-demand
 * "detailed explanation" step — it's pure local regex matching with no
 * Gemini cost, so there's no reason to gate it behind a user click. This is
 * what feeds the dashboard's trend sparklines the moment a report is read.
 */
class ExtractHealthMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public Report $report) {}

    public function handle(HealthMetricExtractor $extractor): void
    {
        $report = $this->report->fresh();
        if (! $report || ! $report->ocr_text) {
            return;
        }

        $recordedDate = $report->report_date?->toDateString()
            ?? $report->uploaded_at?->toDateString()
            ?? now()->toDateString();

        foreach ($extractor->extract($report->ocr_text) as $metric) {
            HealthMetric::firstOrCreate(
                ['report_id' => $report->id, 'metric_type' => $metric['metric_type']],
                [
                    'family_member_id' => $report->family_member_id,
                    'value' => $metric['value'],
                    'unit' => $metric['unit'],
                    'recorded_date' => $recordedDate,
                    'source' => 'ocr_extracted',
                ]
            );
        }
    }
}
