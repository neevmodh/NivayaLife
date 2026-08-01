<?php

namespace App\Services\Ocr;

use App\Services\ClinicalNlp\ClinicalNlpClient;
use Throwable;

/**
 * Single source of truth for "which OCR engine reads this file" — used by
 * both ProcessReportOcrJob (the real background pipeline) and
 * ReportUploadController::detect() (the pre-upload preview). Before this
 * existed, detect() only ever used Tesseract while the actual job tried
 * PaddleOCR first, so the auto-filled type/date/hospital/doctor a user saw
 * before submitting could reflect a weaker read than what the report was
 * actually processed with.
 *
 * PaddleOCR is tried first — more accurate than Tesseract on real-world
 * uploads (phone photos, skewed scans, mixed layouts) — with Tesseract as
 * the fallback whenever PaddleOCR isn't configured, unreachable, or finds
 * nothing usable.
 */
class OcrResolver
{
    public function __construct(
        private readonly OcrExtractor $ocrExtractor,
        private readonly ClinicalNlpClient $clinicalNlp,
    ) {}

    /** @return array{text: string, looks_usable: bool, method: ?string} */
    public function resolve(string $absolutePath, string $mimeType): array
    {
        if ($this->clinicalNlp->isConfigured()) {
            try {
                $images = $this->ocrExtractor->visionImages($absolutePath, $mimeType);
                $paddleResult = $this->clinicalNlp->ocr($images);

                if ($paddleResult['looks_usable']) {
                    return ['text' => $paddleResult['text'], 'looks_usable' => true, 'method' => 'paddleocr'];
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $text = $this->ocrExtractor->extract($absolutePath, $mimeType);
        $usable = $this->ocrExtractor->looksUsable($text);

        return ['text' => $text, 'looks_usable' => $usable, 'method' => $usable ? 'tesseract' : null];
    }
}
