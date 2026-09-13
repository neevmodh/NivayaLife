<?php

namespace App\Services\Reports;

/**
 * Strips the non-clinical furniture out of OCR'd report text before any model
 * sees it: lab letterheads, phone/email/GSTIN lines, accreditation blurbs,
 * "computer generated report" disclaimers, page footers, barcode digits.
 *
 * Every character removed here is a token not paid for downstream, on every
 * subsequent call for that report. It matters most for the report types that
 * have no structured schema to shrink the payload for them.
 *
 * Deliberately conservative: a line is only dropped when it is
 * *unambiguously* furniture. Losing a real result to save tokens would be a
 * terrible trade, so anything uncertain is kept.
 */
class OcrCleaner
{
    /** A line matching any of these is furniture, never clinical content. */
    private const JUNK_LINE = [
        // Contact details
        '/^\s*(ph|tel|phone|mob|mobile|fax|contact)\s*(no\.?)?\s*[:.\-]/i',
        '/^\s*e-?mail\s*[:.\-]/i',
        '/^\s*(web|website|url)\s*[:.\-]/i',
        '/\b[\w.+-]+@[\w-]+\.[\w.]{2,}\b/',
        '/\bwww\.[\w.-]+\b/i',
        // Statutory / registration identifiers
        '/^\s*(gstin|cin|pan|udyam|tan)\b/i',
        '/^\s*reg(istration)?\.?\s*no\.?\s*[:.\-]/i',
        // Accreditation and marketing
        '/\b(nabl|nabh|cap\s+certified|iso\s*\d{4}|accredited)\b/i',
        // Standard disclaimers
        '/computer\s*generated/i',
        '/does\s*not\s*require\s*(a\s*)?signature/i',
        '/^\s*(conditions|terms)\s+of\s+report/i',
        '/not\s+for\s+medico-?legal/i',
        '/^\s*end\s+of\s+report\s*\.?\s*$/i',
        // Pagination and machine artefacts
        '/^\s*page\s*\d+\s*(of|\/)\s*\d+\s*$/i',
        '/^\s*barcode\s*[:.\-]?\s*\d+\s*$/i',
        '/^\s*sid\s*(no\.?)?\s*[:.\-]?\s*\d+\s*$/i',
        // Rules and decorative separators
        '/^\s*[-=_*~.\s]{6,}$/',
    ];

    public static function clean(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $kept = [];

        foreach (preg_split('/\R/u', $text) as $line) {
            if (self::isJunk($line)) {
                continue;
            }

            // Collapse runs of whitespace — OCR of a table emits long gutters
            // of spaces that cost tokens and carry no information.
            $kept[] = trim(preg_replace('/[ \t]{2,}/u', ' ', $line));
        }

        // Blank lines are dropped by isJunk() above, so the result is already
        // dense — no paragraph runs left to collapse.
        return trim(implode("\n", $kept));
    }

    private static function isJunk(string $line): bool
    {
        if (trim($line) === '') {
            return true;
        }

        foreach (self::JUNK_LINE as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }
}
