<?php

namespace App\Services\Ai;

/**
 * Cheap, rule-based safety net for AI-generated medical summaries: an LLM
 * occasionally states a numeric value that isn't actually in the source
 * text (a hallucinated lab value is the worst-case version of this). This
 * never blocks or edits the summary — it only flags numbers that can't be
 * traced back to the OCR text, so a suspicious summary is loggable/reviewable
 * instead of silently trusted.
 */
class SummaryFactChecker
{
    /**
     * @return string[] Numbers mentioned in $content that don't appear anywhere in $sourceText.
     */
    public static function unverifiedNumbers(string $content, string $sourceText): array
    {
        $stated = self::numbers($content);
        $source = self::numbers($sourceText);

        return array_values(array_diff($stated, $source));
    }

    /**
     * Extracts numbers likely to be real measurements (2+ digits, or any
     * decimal) rather than noise — a bare single digit shows up constantly
     * in ordinary prose ("a few questions") and would make this check
     * fire on nearly every summary.
     *
     * @return string[]
     */
    private static function numbers(string $text): array
    {
        preg_match_all('/\d+\.\d+|\d{2,}/', $text, $matches);

        return array_unique($matches[0]);
    }
}
