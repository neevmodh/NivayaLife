<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Best-effort guesses for the fields a user would otherwise type by hand —
 * pure local heuristics (no AI call) so this can run synchronously on every
 * upload without touching the Gemini quota. Every field it returns is still
 * shown as an editable, pre-filled form field, never saved silently, so a
 * wrong guess just costs the user a correction, not a bad record.
 */
class ReportFieldDetector
{
    private const TYPE_KEYWORDS = [
        'prescription' => ['prescription', ' rx ', 'sig:', 'dosage', 'tablet', 'capsule', 'take as directed'],
        'xray' => ['x-ray', 'xray', 'radiograph'],
        'mri_ct' => ['mri', 'ct scan', 'computed tomography', 'magnetic resonance'],
        'ecg' => ['ecg', 'ekg', 'electrocardiogram', 'cardiogram'],
        'insurance' => ['insurance', 'policy no', 'mediclaim', 'sum insured', 'premium'],
        'bill' => ['invoice', 'receipt', 'amount paid', 'bill no', 'total amount', 'grand total'],
        'blood_test' => ['cbc', 'complete blood count', 'hemoglobin', 'blood test', 'lipid profile', 'blood sugar', 'hba1c', 'lft', 'kft', 'pathology', 'specimen'],
    ];

    private const DATE_FORMATS = [
        'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'Y-m-d', 'Y/m/d',
        'd M Y', 'j M Y', 'd F Y', 'j F Y',
        'M d, Y', 'M j, Y', 'F d, Y', 'F j, Y',
    ];

    private const HOSPITAL_KEYWORDS = ['hospital', 'clinic', 'lab', 'laboratory', 'diagnostic', 'medical center', 'medical centre', 'healthcare', 'pathology', 'imaging center', 'imaging centre'];

    /** @return array{type: ?string, report_date: ?string, hospital_or_clinic_name: ?string, doctor_name: ?string} */
    public function detect(string $ocrText, string $filename, Collection $knownHospitals, Collection $knownDoctors): array
    {
        return [
            'type' => $this->detectType($ocrText, $filename),
            'report_date' => $this->detectDate($ocrText),
            'hospital_or_clinic_name' => $this->detectHospital($ocrText, $knownHospitals),
            'doctor_name' => $this->detectDoctor($ocrText, $knownDoctors),
        ];
    }

    private function detectType(string $ocrText, string $filename): ?string
    {
        $haystack = strtolower($ocrText.' '.$filename);
        $scores = [];

        foreach (self::TYPE_KEYWORDS as $type => $keywords) {
            $score = array_sum(array_map(fn ($kw) => substr_count($haystack, $kw), $keywords));
            if ($score > 0) {
                $scores[$type] = $score;
            }
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);

        return array_key_first($scores);
    }

    private function detectDate(string $ocrText): ?string
    {
        $patterns = [
            '/\b\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{4}\b/',
            '/\b\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}\b/',
            '/\b\d{1,2}(?:st|nd|rd|th)?\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec)[a-z]*,?\s+\d{4}\b/i',
            '/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec)[a-z]*\s+\d{1,2}(?:st|nd|rd|th)?,?\s+\d{4}\b/i',
        ];

        $today = Carbon::today();
        $candidates = collect();

        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $ocrText, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($matches[0] as [$raw, $pos]) {
                $date = $this->tryParseDate($raw);
                if ($date && $date->lte($today->copy()->addDay()) && $date->gte($today->copy()->subYears(100))) {
                    $candidates->push(['date' => $date, 'pos' => $pos, 'raw' => $raw]);
                }
            }
        }

        if ($candidates->isEmpty()) {
            return null;
        }

        $keywordPattern = '/\b(report(?:ed)?|collect(?:ed)?|date|issued|registered|sample)\b/i';

        return $candidates
            ->map(function ($c) use ($ocrText, $keywordPattern) {
                $windowStart = max(0, $c['pos'] - 30);
                $window = substr($ocrText, $windowStart, $c['pos'] - $windowStart);
                $c['score'] = preg_match($keywordPattern, $window) ? 2 : 1;

                return $c;
            })
            ->sortByDesc('score')
            ->first()['date']
            ->toDateString();
    }

    private function tryParseDate(string $raw): ?Carbon
    {
        $clean = trim(preg_replace('/(\d)(st|nd|rd|th)/i', '$1', $raw));

        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $clean);
                if ($date && $date->format($format) === $clean) {
                    return $date;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /** Checks this family's previously-used hospital names first — a real match there beats any generic heuristic. */
    private function detectHospital(string $ocrText, Collection $knownHospitals): ?string
    {
        foreach ($knownHospitals as $name) {
            if ($name && stripos($ocrText, $name) !== false) {
                return $name;
            }
        }

        $lines = array_slice(array_values(array_filter(array_map('trim', explode("\n", $ocrText)))), 0, 8);

        foreach ($lines as $line) {
            if (strlen($line) > 80) {
                continue;
            }
            $lower = strtolower($line);
            foreach (self::HOSPITAL_KEYWORDS as $kw) {
                if (str_contains($lower, $kw)) {
                    return $this->stripTrailingContactNoise($line);
                }
            }
        }

        return null;
    }

    /** Letterheads often cram a phone/email onto the same line as the name — keep only the name part before that noise starts. */
    private function stripTrailingContactNoise(string $line): string
    {
        $line = preg_replace('/\s*\|.*$/', '', $line);
        $line = preg_replace('/\s*[:\-]?\s*(?:\+?\d[\d\s]{6,}\d).*$/', '', $line);

        return trim($line, " \t\n\r\0\x0B-:|");
    }

    private function detectDoctor(string $ocrText, Collection $knownDoctors): ?string
    {
        foreach ($knownDoctors as $name) {
            if ($name && stripos($ocrText, $name) !== false) {
                return $name;
            }
        }

        // Capped at two name-words after "Dr." (first + last) — a third
        // capitalized word this greedy is usually document noise ("Dr. Hiren
        // Shah Reported") rather than a real middle/second surname.
        if (preg_match('/Dr\.?\s+[A-Z][a-zA-Z.]+(?:\s+[A-Z][a-zA-Z.]+){0,1}/', $ocrText, $m)) {
            return trim($m[0]);
        }

        return null;
    }
}
