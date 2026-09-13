<?php

namespace App\Services\Reports;

/**
 * Decides whether a lab value is low/normal/high by direct numeric
 * comparison against its printed reference range.
 *
 * This exists because a direct comparison is strictly more reliable than an
 * LLM's arithmetic — every extractor in the pipeline asks a model for a flag
 * and then lets this override it. It is the safety net the whole report
 * pipeline leans on, so it is deliberately a pure, heavily-tested function.
 *
 * Returns null only when the range is genuinely not a numeric threshold
 * ("Negative", "Reactive", "See note"), in which case the caller should keep
 * whatever the model said rather than guessing.
 */
class LabFlag
{
    /** Unicode ≤ / ≥ and their ASCII spellings, plus the "less than" words labs sometimes print. */
    private const LTE = ['≤', '<=', '&le;'];

    private const GTE = ['≥', '>=', '&ge;'];

    public static function for(string $value, ?string $range): ?string
    {
        if ($range === null) {
            return null;
        }

        $numericValue = self::parseNumber($value);

        if ($numericValue === null) {
            return null;
        }

        $range = self::normalize($range);

        // "13.0 - 17.0" — the common two-sided band. Also catches "13-17",
        // "1,500-4,000" and en/em dashes via normalize().
        if (preg_match('/^(-?[\d.]+)\s*-\s*(-?[\d.]+)$/', $range, $m)) {
            $low = (float) $m[1];
            $high = (float) $m[2];

            return match (true) {
                $numericValue < $low => 'low',
                $numericValue > $high => 'high',
                default => 'normal',
            };
        }

        // One-sided thresholds: "<200", "≤5", ">40", ">=0.5". These are
        // extremely common on lipid panels and were previously punted on,
        // which meant a wrong model flag survived on exactly the values
        // (cholesterol, HDL) where being wrong matters most.
        if (preg_match('/^(<=|>=|<|>)\s*(-?[\d.]+)$/', $range, $m)) {
            $threshold = (float) $m[2];

            return match ($m[1]) {
                '<' => $numericValue < $threshold ? 'normal' : 'high',
                '<=' => $numericValue <= $threshold ? 'normal' : 'high',
                '>' => $numericValue > $threshold ? 'normal' : 'low',
                '>=' => $numericValue >= $threshold ? 'normal' : 'low',
            };
        }

        // Qualitative or unrecognised — defer to the model.
        return null;
    }

    /**
     * Leading number of a result. Handles "13.8", "13.8 g/dL", "1,500" and a
     * value the model returned as a float rather than a string. A result that
     * is itself qualitative ("Negative") yields null.
     */
    private static function parseNumber(string $value): ?float
    {
        $value = str_replace(',', '', trim($value));

        return preg_match('/^-?\d*\.?\d+/', $value, $m) ? (float) $m[0] : null;
    }

    private static function normalize(string $range): string
    {
        $range = str_replace(self::LTE, '<=', $range);
        $range = str_replace(self::GTE, '>=', $range);

        // En dash / em dash / minus sign all get printed as range separators.
        $range = str_replace(['–', '—', '−'], '-', $range);

        // Thousands separators, and the "to" some labs spell out.
        $range = str_ireplace(' to ', '-', $range);
        $range = str_replace([',', ' '], '', $range);

        return trim($range);
    }
}
