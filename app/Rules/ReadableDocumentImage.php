<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects a photo too small for OCR to read reliably.
 *
 * This exists because the failure is silent and dangerous rather than obvious:
 * below roughly 300 DPI Tesseract starts dropping decimal points, and on a lab
 * report that means "7.8" becomes "78" — the pipeline then reported an HbA1c of
 * 78% as fact. OcrExtractor now upscales small images, which recovers a lot,
 * but interpolation cannot invent detail that was never captured.
 *
 * Threshold measured, not guessed. Running real documents through the full
 * pipeline at decreasing widths:
 *
 *   width   blood test (table)   discharge summary (prose)
 *   410px   pass                 FAIL — nothing usable extracted
 *   533px   pass                 pass
 *   697px   pass                 pass
 *
 * Tables survive further down because numbers are short and redundant; dense
 * prose collapses first. The cliff sits between 410 and 533, so the floor is
 * set at 600 to leave margin — those were pristine synthetic renders, and a
 * real phone photo carries blur, skew and JPEG artefacts that a rendered PDF
 * does not.
 *
 * PDFs are exempt: pdfToImages() rasterises them at 300 DPI regardless of how
 * the file was produced.
 */
class ReadableDocumentImage implements ValidationRule
{
    public const MIN_SHORTER_SIDE = 600;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        // Only raster images carry a fixed pixel budget. PDFs and Word files
        // are re-rendered or read as text downstream.
        if (! str_starts_with((string) $value->getMimeType(), 'image/')) {
            return;
        }

        $size = @getimagesize($value->getRealPath());

        // Unreadable header — let the existing mime validation and the OCR
        // job's own error handling deal with it rather than guessing here.
        if ($size === false) {
            return;
        }

        [$width, $height] = $size;
        $shorter = min($width, $height);

        if ($shorter < self::MIN_SHORTER_SIDE) {
            $fail(
                "This image is too small to read reliably ({$width}×{$height} pixels). "
                .'Values can be misread at this size. Please retake the photo closer to the document, '
                .'or upload a higher-quality scan — at least '.self::MIN_SHORTER_SIDE.' pixels on the shorter side.'
            );
        }
    }
}
