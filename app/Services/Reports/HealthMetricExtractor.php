<?php

namespace App\Services\Reports;

/**
 * Pattern-matches common lab values out of OCR text. Deliberately simple
 * regexes tuned for the label wording Indian lab reports and prescriptions
 * actually use — this is a best-effort feed for dashboard trend sparklines,
 * not a diagnostic parser, so false negatives (missing a value) are far
 * preferable to false positives (inserting a wrong one).
 *
 * @return array<int, array{metric_type: string, value: float, unit: ?string}>
 */
class HealthMetricExtractor
{
    private const PATTERNS = [
        'blood_sugar_fasting' => [
            'regex' => '/(?:fasting\s*(?:blood\s*)?(?:sugar|glucose)|FBS)\s*[:\-]?\s*(\d{2,3}(?:\.\d+)?)/i',
            'unit' => 'mg/dL',
        ],
        'blood_sugar_pp' => [
            'regex' => '/(?:pp\s*(?:blood\s*)?(?:sugar|glucose)|post[\s-]?prandial(?:\s*(?:blood\s*)?(?:sugar|glucose))?|PPBS)\s*[:\-]?\s*(\d{2,3}(?:\.\d+)?)/i',
            'unit' => 'mg/dL',
        ],
        'hba1c' => [
            'regex' => '/(?:HbA1c|Hb\s*A1c|glycated\s*h[ae]emoglobin)\s*[:\-]?\s*(\d{1,2}(?:\.\d{1,2})?)\s*%?/i',
            'unit' => '%',
        ],
        'cholesterol_total' => [
            'regex' => '/(?:total\s*cholesterol|cholesterol[,\s]*total)\s*[:\-]?\s*(\d{2,3}(?:\.\d+)?)/i',
            'unit' => 'mg/dL',
        ],
        'cholesterol_ldl' => [
            'regex' => '/LDL(?:\s*cholesterol)?\s*[:\-]?\s*(\d{2,3}(?:\.\d+)?)/i',
            'unit' => 'mg/dL',
        ],
        'cholesterol_hdl' => [
            'regex' => '/HDL(?:\s*cholesterol)?\s*[:\-]?\s*(\d{2,3}(?:\.\d+)?)/i',
            'unit' => 'mg/dL',
        ],
        'hemoglobin' => [
            'regex' => '/\bH(?:ae|e)moglobin\b\s*[:\-]?\s*(\d{1,2}(?:\.\d{1,2})?)|(?<!A1c\s)(?<!A1c)\bHb\b(?!\s*A1c)\s*[:\-]?\s*(\d{1,2}(?:\.\d{1,2})?)\s*g/i',
            'unit' => 'g/dL',
        ],
    ];

    /** @return array<int, array{metric_type: string, value: float, unit: ?string}> */
    public function extract(string $ocrText): array
    {
        $results = [];

        foreach (self::PATTERNS as $metricType => $spec) {
            if (preg_match($spec['regex'], $ocrText, $matches)) {
                $value = $this->firstNumericGroup($matches);
                if ($value !== null) {
                    $results[] = ['metric_type' => $metricType, 'value' => $value, 'unit' => $spec['unit']];
                }
            }
        }

        $bp = $this->extractBloodPressure($ocrText);
        if ($bp !== null) {
            $results[] = ['metric_type' => 'blood_pressure_systolic', 'value' => $bp[0], 'unit' => 'mmHg'];
            $results[] = ['metric_type' => 'blood_pressure_diastolic', 'value' => $bp[1], 'unit' => 'mmHg'];
        }

        return $results;
    }

    /** @return array{0: float, 1: float}|null [systolic, diastolic] */
    private function extractBloodPressure(string $text): ?array
    {
        if (preg_match('/(?:BP|Blood\s*Pressure)\s*[:\-]?\s*(\d{2,3})\s*\/\s*(\d{2,3})/i', $text, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }

        // Fallback: a bare "120/80"-shaped number in a plausible human BP range.
        if (preg_match('/\b(\d{2,3})\s*\/\s*(\d{2,3})\b/', $text, $m)) {
            $sys = (int) $m[1];
            $dia = (int) $m[2];
            if ($sys >= 70 && $sys <= 220 && $dia >= 40 && $dia <= 140 && $sys > $dia) {
                return [(float) $sys, (float) $dia];
            }
        }

        return null;
    }

    private function firstNumericGroup(array $matches): ?float
    {
        foreach (array_slice($matches, 1) as $group) {
            if ($group !== '' && $group !== null) {
                return (float) $group;
            }
        }

        return null;
    }
}
