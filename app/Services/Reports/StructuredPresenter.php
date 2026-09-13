<?php

namespace App\Services\Reports;

use App\Models\Report;

/**
 * Turns the per-type structured_data shape into something the report page can
 * render, so the Blade view doesn't need a branch per report type.
 *
 * Returns null when there is nothing worth showing — the summary and the
 * original document are still there, so an empty extraction simply renders
 * nothing rather than an empty card.
 */
class StructuredPresenter
{
    /**
     * @return array{partial: string, props: array}|null
     */
    public static function for(Report $report): ?array
    {
        $data = $report->structured_data;

        if (! is_array($data) || $data === []) {
            return null;
        }

        return match (ReportSchema::shapeFor($report->type)) {
            // Result tables already have a dedicated table in the view.
            'results' => null,
            'medicines' => ($data['medicines'] ?? []) ? ['partial' => 'reports.partials.structured.medicines', 'props' => ['data' => $data]] : null,
            'imaging' => ['partial' => 'reports.partials.structured.imaging', 'props' => ['data' => $data]],
            'findings' => self::findings($data),
            'ecg' => self::pairs('ECG readings', self::ecgPairs($data)),
            'discharge' => self::discharge($data),
            'bill' => self::bill($data),
            'insurance' => self::insurance($data),
            'eye' => self::pairs('Prescription', self::eyePairs($data)),
            default => self::pairs('Details', self::genericPairs($data)),
        };
    }

    /** Dental shares the imaging partial's findings list. */
    private static function findings(array $data): ?array
    {
        $findings = array_merge($data['findings'] ?? [], $data['procedures'] ?? []);

        return $findings
            ? ['partial' => 'reports.partials.structured.imaging', 'props' => ['data' => ['findings' => $findings, 'impression' => null, 'body_part' => null]]]
            : null;
    }

    private static function ecgPairs(array $data): array
    {
        $pairs = self::labelled($data, [
            'heart_rate' => 'Heart rate',
            'rhythm' => 'Rhythm',
            'interpretation' => 'Interpretation',
        ]);

        foreach ($data['intervals'] ?? [] as $name => $value) {
            if (self::usable($value)) {
                $pairs[] = ['label' => (string) $name, 'value' => (string) $value];
            }
        }

        return $pairs;
    }

    private static function eyePairs(array $data): array
    {
        $pairs = [];

        foreach (['right_eye' => 'Right eye', 'left_eye' => 'Left eye'] as $key => $label) {
            $eye = $data[$key] ?? null;

            if (! is_array($eye)) {
                continue;
            }

            $parts = [];
            foreach (['sphere' => 'SPH', 'cylinder' => 'CYL', 'axis' => 'Axis', 'va' => 'VA'] as $f => $short) {
                if (self::usable($eye[$f] ?? null)) {
                    $parts[] = "{$short} {$eye[$f]}";
                }
            }

            if ($parts) {
                $pairs[] = ['label' => $label, 'value' => implode(' · ', $parts)];
            }
        }

        return array_merge($pairs, self::labelled($data, ['diagnosis' => 'Diagnosis', 'advice' => 'Advice']));
    }

    private static function discharge(array $data): ?array
    {
        $pairs = self::labelled($data, [
            'admission_date' => 'Admitted',
            'discharge_date' => 'Discharged',
            'follow_up' => 'Follow-up',
        ]);

        foreach (['diagnoses' => 'Diagnosis', 'procedures' => 'Procedure'] as $key => $label) {
            foreach ($data[$key] ?? [] as $item) {
                if (self::usable($item)) {
                    $pairs[] = ['label' => $label, 'value' => is_array($item) ? json_encode($item) : (string) $item];
                }
            }
        }

        return self::pairs('Hospital stay', $pairs);
    }

    private static function bill(array $data): ?array
    {
        $pairs = [];

        foreach ($data['line_items'] ?? [] as $item) {
            if (is_array($item) && self::usable($item['description'] ?? null)) {
                $pairs[] = ['label' => (string) $item['description'], 'value' => trim(($data['currency'] ?? '').' '.($item['amount'] ?? ''))];
            }
        }

        if (self::usable($data['total_amount'] ?? null)) {
            $pairs[] = ['label' => 'Total', 'value' => trim(($data['currency'] ?? '').' '.$data['total_amount'])];
        }

        return self::pairs('Bill breakdown', $pairs);
    }

    private static function insurance(array $data): ?array
    {
        return self::pairs('Policy details', self::labelled($data, [
            'insurer' => 'Insurer',
            'policy_number' => 'Policy number',
            'policyholder' => 'Policyholder',
            'sum_insured' => 'Sum insured',
            'valid_from' => 'Valid from',
            'valid_to' => 'Valid to',
        ]));
    }

    /** The `other` shape, plus anything unrecognised. */
    private static function genericPairs(array $data): array
    {
        $pairs = [];

        foreach ($data['key_values'] ?? [] as $kv) {
            if (is_array($kv) && self::usable($kv['label'] ?? null) && self::usable($kv['value'] ?? null)) {
                $pairs[] = ['label' => (string) $kv['label'], 'value' => (string) $kv['value']];
            }
        }

        return $pairs;
    }

    /** @param array<string,string> $map field => display label */
    private static function labelled(array $data, array $map): array
    {
        $pairs = [];

        foreach ($map as $field => $label) {
            if (self::usable($data[$field] ?? null)) {
                $pairs[] = ['label' => $label, 'value' => (string) $data[$field]];
            }
        }

        return $pairs;
    }

    private static function pairs(string $title, array $pairs): ?array
    {
        return $pairs
            ? ['partial' => 'reports.partials.structured.key-values', 'props' => ['data' => ['pairs' => $pairs], 'title' => $title]]
            : null;
    }

    /**
     * Models frequently emit the string "null" or echo a placeholder back
     * rather than omitting a field — none of which should reach the UI.
     */
    private static function usable(mixed $value): bool
    {
        if ($value === null || is_array($value)) {
            return false;
        }

        $value = trim((string) $value);

        return $value !== ''
            && strtolower($value) !== 'null'
            && ! str_starts_with($value, '<');
    }
}
