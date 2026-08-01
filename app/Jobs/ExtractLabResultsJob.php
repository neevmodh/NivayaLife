<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Report;
use App\Services\Ai\AiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Parses the actual lab-value table (test name, result, reference range,
 * flag) out of a blood_test report's OCR text — the structured data a
 * regex-only extractor (HealthMetricExtractor, which only knows ~7
 * hardcoded metric names) can't reliably pull from an arbitrary lab's table
 * layout. Display-only enrichment: any failure here leaves lab_results null
 * and the report's short summary/health metrics are unaffected.
 */
class ExtractLabResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    private const MAX_OCR_CHARS = 8000;

    public function __construct(public Report $report) {}

    public function handle(AiClient $ai): void
    {
        $report = $this->report->fresh();

        if (! $report || $report->type !== 'blood_test' || ! $report->ocr_text) {
            return;
        }

        if (! $ai->hasAvailableCredential()) {
            return;
        }

        $aiJob = AiJob::create([
            'report_id' => $report->id,
            'job_type' => 'entity_extraction',
            'status' => 'running',
            'provider' => 'gemini',
            'started_at' => now(),
        ]);

        try {
            $result = $ai->generate($this->buildPrompt($report));
            $rows = $this->parseRows($result['text']);

            $report->update(['lab_results' => $rows]);

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
        }
    }

    /** @return array<int, array{test: string, value: string, unit: ?string, reference_range: ?string, flag: string}> */
    private function parseRows(string $rawResponse): array
    {
        // Models occasionally wrap JSON in a markdown code fence despite
        // being told not to — strip that before decoding rather than
        // failing the whole extraction over formatting.
        $cleaned = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($rawResponse)));

        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded)) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (! is_array($row) || ! isset($row['test'], $row['value']) || $row['test'] === '' || $row['value'] === '') {
                continue;
            }

            $unit = isset($row['unit']) && $row['unit'] !== '' ? (string) $row['unit'] : null;
            $range = isset($row['reference_range']) && $row['reference_range'] !== '' ? (string) $row['reference_range'] : null;
            $flag = $this->normalizeFlag((string) ($row['flag'] ?? 'unknown'));

            $rows[] = [
                'test' => (string) $row['test'],
                'value' => (string) $row['value'],
                'unit' => $unit,
                'reference_range' => $range,
                'flag' => $this->recomputeFlag((string) $row['value'], $range) ?? $flag,
            ];
        }

        return $rows;
    }

    private function normalizeFlag(string $flag): string
    {
        $flag = strtolower(trim($flag));

        return in_array($flag, ['low', 'high', 'normal', 'borderline', 'abnormal'], true) ? $flag : 'unknown';
    }

    /**
     * A direct numeric comparison is strictly more reliable than an LLM's
     * arithmetic — whenever both the value and the reference range are
     * plain numbers (the common "13.0-17.0" shape), recompute the flag
     * ourselves and let it override whatever the model said. Returns null
     * (defer to the model's flag) for anything non-numeric — qualitative
     * ranges like "Negative" or "<200" aren't safe to parse this way.
     */
    private function recomputeFlag(string $value, ?string $range): ?string
    {
        if ($range === null || ! preg_match('/^\s*([\d.]+)\s*-\s*([\d.]+)\s*$/', $range, $rangeMatch)) {
            return null;
        }

        if (! preg_match('/^\s*([\d.]+)\s*/', $value, $valueMatch)) {
            return null;
        }

        $numericValue = (float) $valueMatch[1];
        $low = (float) $rangeMatch[1];
        $high = (float) $rangeMatch[2];

        if ($numericValue < $low) {
            return 'low';
        }

        if ($numericValue > $high) {
            return 'high';
        }

        return 'normal';
    }

    private function buildPrompt(Report $report): string
    {
        $ocrText = Str::limit($report->ocr_text, self::MAX_OCR_CHARS, '');

        return <<<PROMPT
        Below is text extracted via OCR from a blood test / lab report — it may contain minor OCR errors and its table structure may be flattened into plain lines.

        Extract every individual test result row into a JSON array. Each element must have exactly these keys:
        - "test": the test/parameter name (e.g. "Hemoglobin", "Total Cholesterol")
        - "value": the measured result, as a string, numbers only where possible (e.g. "12.5")
        - "unit": the unit of measurement, or null if none is given
        - "reference_range": the normal/reference range as printed (e.g. "13.0-17.0"), or null if not given
        - "flag": one of "low", "high", "normal", "borderline", "abnormal", or "unknown" — based on comparing the value against the reference range

        Only include rows that represent an actual test with a result — skip headers, patient demographics, lab addresses, doctor names, signatures, and instrument/interpretation notes. Output ONLY the raw JSON array, no markdown formatting, no explanation, no code fence.

        OCR TEXT:
        {$ocrText}
        PROMPT;
    }
}
