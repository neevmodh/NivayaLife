<?php

namespace App\Services\Reports;

use App\Services\Ai\AiClient;
use App\Services\Ollama\OllamaClient;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Turns cleaned OCR text into the structured shape ReportSchema defines for
 * that report type.
 *
 * Routed to the self-hosted Ollama instance first because extraction is the
 * token-heavy half of the pipeline and a small local model does it well —
 * measured on a real lab report, it found every result and correctly ignored
 * the letterhead, barcode and disclaimers. Gemini/Groq remain the fallback
 * when Ollama is unconfigured or unreachable, mirroring the fallback chain
 * AiChatController already uses.
 *
 * The model is never trusted for flags: LabFlag recomputes them numerically
 * and overrides whatever was returned.
 */
class StructuredExtractor
{
    private const MAX_OCR_CHARS = 8000;

    /** Comfortably inside ExtractStructuredDataJob's 240s job timeout, and well above what a shared-CPU container needs for a few hundred structured tokens. */
    private const OLLAMA_TIMEOUT = 180;

    public function __construct(
        private readonly OllamaClient $ollama,
        private readonly AiClient $ai,
    ) {}

    /**
     * @return array{data: array, provider: string, input_tokens: ?int, output_tokens: ?int}
     */
    public function extract(string $type, string $ocrText): array
    {
        $text = Str::limit(OcrCleaner::clean($ocrText), self::MAX_OCR_CHARS, '');

        if (trim($text) === '') {
            throw new RuntimeException('No usable text to extract from.');
        }

        $prompt = $this->buildPrompt($type, $text);

        // A small local model occasionally emits a truncated or malformed
        // object. Observed in testing: the same document parsed 7/7 rows on
        // three runs and produced nothing on a fourth. Rather than silently
        // storing an empty structure, escalate that one document to the paid
        // chain — the whole point of extraction is that it is reliable.
        if ($this->ollama->isConfigured()) {
            try {
                $local = $this->ollama->generate($prompt, temperature: 0, timeout: self::OLLAMA_TIMEOUT);
                $data = $this->normalize($type, $this->decode($local['text']));

                if (! self::isEmpty($data)) {
                    return [
                        'data' => $data,
                        'provider' => 'ollama',
                        'input_tokens' => $local['input_tokens'],
                        'output_tokens' => $local['output_tokens'],
                    ];
                }
            } catch (Throwable $e) {
                // Unreachable or erroring — fall through to the paid chain
                // rather than failing the report.
                report($e);
            }
        }

        $result = $this->ai->generate($prompt);

        return [
            'data' => $this->normalize($type, $this->decode($result['text'])),
            'provider' => $result['provider'],
            'input_tokens' => $result['input_tokens'],
            'output_tokens' => $result['output_tokens'],
        ];
    }

    /** Nothing worth storing — every schema key is absent or an empty array. */
    private static function isEmpty(array $data): bool
    {
        foreach ($data as $key => $value) {
            if ($key === 'not_a_medical_report') {
                continue;
            }
            if (is_array($value) ? $value !== [] : ($value !== null && $value !== '')) {
                return false;
            }
        }

        // An explicit "this isn't a medical report" verdict is a real result.
        return ! ($data['not_a_medical_report'] ?? false);
    }

    private function buildPrompt(string $type, string $text): string
    {
        $schema = ReportSchema::for($type);

        return <<<PROMPT
        Extract structured data from the medical report text below. It came from OCR and may contain minor errors and flattened table layout.

        {$schema['instruction']}

        Return ONLY a single raw JSON object matching this shape — no markdown, no code fence, no commentary:
        {$schema['example']}

        Rules:
        - The angle-bracket entries above are PLACEHOLDERS describing each field. Never copy them, and never copy their wording into your answer — replace them with values read from the text.
        - Produce one array element for every matching row in the text. Do not stop after the first, and do not summarise.
        - Never invent a value that is not in the text. Use null when something is absent.
        - Copy values and reference ranges exactly as printed, including symbols like "<" or ">".
        - Put only the measurement in "value"; the unit belongs in "unit".
        - If the text contains no extractable data of this kind, return the shape above with empty arrays.

        REPORT TEXT:
        {$text}
        PROMPT;
    }

    /**
     * Models occasionally wrap JSON in a markdown fence or emit a leading
     * sentence despite being told not to — recover the object rather than
     * failing the whole extraction over formatting.
     */
    private function decode(string $raw): array
    {
        $cleaned = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($raw)));

        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded)) {
            // Last resort: grab the outermost {...} block.
            if (preg_match('/\{.*\}/s', $cleaned, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Keeps only the keys the schema defines, and recomputes every flag
     * numerically. An empty/garbage response degrades to an empty structure
     * rather than propagating nonsense.
     */
    private function normalize(string $type, array $data): array
    {
        if (! ReportSchema::hasMeasuredResults($type)) {
            return $data;
        }

        $rows = [];

        foreach ($data['results'] ?? [] as $row) {
            if (! is_array($row) || ($row['test'] ?? '') === '' || ($row['value'] ?? '') === '') {
                continue;
            }

            $unit = isset($row['unit']) && $row['unit'] !== '' ? (string) $row['unit'] : null;
            $value = self::splitUnitOutOfValue((string) $row['value'], $unit);
            $range = isset($row['reference_range']) && $row['reference_range'] !== ''
                ? (string) $row['reference_range']
                : null;

            $rows[] = [
                'test' => (string) $row['test'],
                'value' => $value,
                'unit' => $unit,
                'reference_range' => $range,
                // Numeric comparison wins over the model's arithmetic; the
                // model's answer only survives for genuinely qualitative ranges.
                'flag' => LabFlag::for($value, $range) ?? $this->normalizeFlag((string) ($row['flag'] ?? 'unknown')),
            ];
        }

        $data['results'] = $rows;

        return $data;
    }

    /**
     * The model sometimes glues the unit onto the measurement ("11.2 g/dL")
     * despite being told not to, which then renders as "11.2 g/dL g/dL".
     * Drop the trailing unit when it simply repeats the unit field.
     */
    private static function splitUnitOutOfValue(string $value, ?string $unit): string
    {
        $value = trim($value);

        if ($unit !== null && str_ends_with(strtolower($value), strtolower(trim($unit)))) {
            $stripped = trim(substr($value, 0, -strlen(trim($unit))));

            // Only accept the strip if what remains still looks like a measurement.
            if ($stripped !== '' && preg_match('/\d/', $stripped)) {
                return $stripped;
            }
        }

        return $value;
    }

    private function normalizeFlag(string $flag): string
    {
        $flag = strtolower(trim($flag));

        return in_array($flag, ['low', 'high', 'normal', 'borderline', 'abnormal'], true) ? $flag : 'unknown';
    }
}
