<?php

namespace App\Services\Ocr;

use Illuminate\Process\PendingProcess;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Imagick;
use RuntimeException;

/**
 * PDFs are rasterized page-by-page with Imagick (which shells out to
 * Ghostscript for the PDF delegate) before Tesseract ever sees them, since
 * Tesseract only reads raster images. Photos go through a heavier cleanup
 * pass than PDF pages — real phone photos of prescriptions are rarely flat
 * and well-lit, while a PDF is already a clean, aligned scan.
 */
class OcrExtractor
{
    /** Concurrent Tesseract processes are capped so a long document can't spawn dozens at once and blow past the host's CPU/memory. */
    private const MAX_CONCURRENT_PAGES = 4;

    /**
     * Shared with the upload-time /reports/detect endpoint: detection runs
     * OCR against the file before a Report even exists so it can pre-fill
     * the form, and caches the result under this key so the real upload
     * moments later (ProcessReportOcrJob) can reuse it instead of paying
     * for the same Tesseract work twice.
     */
    public static function cacheKey(string $fileHash): string
    {
        return "report_ocr:{$fileHash}";
    }

    /** @return string Extracted text, empty string if nothing readable was found. */
    public function extract(string $absolutePath, string $mimeType): string
    {
        $tempFiles = [];

        try {
            $pageImages = $mimeType === 'application/pdf'
                ? $this->pdfToImages($absolutePath, $tempFiles)
                : [$this->preprocessImage($absolutePath, $tempFiles)];

            $multiPage = count($pageImages) > 1;
            $rawTexts = $this->runTesseractBatch($pageImages, 'eng');

            $pageTexts = [];
            foreach ($pageImages as $index => $imagePath) {
                $text = $rawTexts[$index];

                // English-only missing it usually means the page actually
                // has non-Latin text (Hindi/Gujarati headers, stamps, or
                // patient details are common on Indian lab reports) —
                // Tesseract's multi-language mode reads those, but it also
                // measurably hurts accuracy on plain English text, which is
                // the common case, so it's only worth paying for here.
                if (! $this->looksUsable($text) && $this->multiLanguages() !== 'eng') {
                    $wider = trim($this->runTesseract($imagePath, $this->multiLanguages()));
                    if ($this->alnumCount($wider) > $this->alnumCount($text)) {
                        $text = $wider;
                    }
                }

                // A failed/garbled read is often a sideways or upside-down
                // phone photo — Tesseract's own deskew only fixes small
                // tilts, not 90/180/270 rotations. This fallback only runs
                // on the rare page that actually needs it, so the common
                // case pays no extra cost.
                if (! $this->looksUsable($text)) {
                    $text = $this->retryRotated($imagePath, $tempFiles) ?? $text;
                }

                $pageTexts[] = $multiPage ? '--- Page '.($index + 1)." ---\n".$text : $text;
            }

            return trim(implode("\n\n", $pageTexts));
        } finally {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Runs Tesseract across all pages, at most MAX_CONCURRENT_PAGES at a
     * time — each page is an independent, CPU-bound invocation, so running
     * a batch concurrently turns its wall-clock OCR time into roughly
     * max(page time) instead of sum(page time), without letting a huge
     * document spawn unbounded processes at once.
     *
     * @param  string[]  $pageImages
     * @return array<int, string> Keyed the same as $pageImages.
     */
    private function runTesseractBatch(array $pageImages, string $languages): array
    {
        $texts = [];
        $anySucceeded = false;
        $lastError = null;

        foreach (array_chunk($pageImages, self::MAX_CONCURRENT_PAGES, preserve_keys: true) as $batch) {
            $results = Process::pool(function (Pool $pool) use ($batch, $languages) {
                foreach ($batch as $key => $imagePath) {
                    $this->configureTesseract($pool->as($key), $imagePath, $languages);
                }
            })->run();

            foreach ($batch as $key => $imagePath) {
                $result = $results[$key];
                $texts[$key] = trim($result->output());
                if ($result->successful()) {
                    $anySucceeded = true;
                } else {
                    $lastError = $result->errorOutput();
                }
            }
        }

        if (! $anySucceeded) {
            throw new RuntimeException('Tesseract failed: '.$lastError);
        }

        return $texts;
    }

    /**
     * Rough signal for "did OCR actually read something, or is this noise?"
     * Scanned garbage tends to be short and symbol-heavy; real extracted text
     * has a healthy amount of letters/digits relative to its length.
     */
    public function looksUsable(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        $alnumCount = $this->alnumCount($text);

        // mb_strlen (characters), not strlen (bytes) — Devanagari/Gujarati
        // characters are multi-byte in UTF-8, so a byte count would deflate
        // this ratio for perfectly good non-Latin OCR text.
        return $alnumCount >= 15 && ($alnumCount / max(mb_strlen($text, 'UTF-8'), 1)) >= 0.4;
    }

    /**
     * Counts Latin alphanumerics plus Devanagari (Hindi) and Gujarati script
     * characters — a report OCR'd correctly in one of those scripts would
     * otherwise score as unreadable noise, since neither is in [A-Za-z0-9].
     */
    private function alnumCount(string $text): int
    {
        return preg_match_all('/[A-Za-z0-9]|[\x{0900}-\x{097F}]|[\x{0A80}-\x{0AFF}]/u', $text);
    }

    /**
     * Tries the other three orientations and keeps whichever reads best.
     * Uses the full language set rather than English-only — by this point
     * English-only has already failed at every orientation attempted so
     * far, so it's worth the wider (if slightly less precise) net.
     */
    private function retryRotated(string $imagePath, array &$tempFiles): ?string
    {
        $best = null;
        $bestScore = 0;

        foreach ([90, 180, 270] as $degrees) {
            $rotatedPath = $this->rotatedCopy($imagePath, $degrees, $tempFiles);
            $text = trim($this->runTesseract($rotatedPath, $this->multiLanguages()));
            $score = $this->alnumCount($text);

            if ($score > $bestScore) {
                $best = $text;
                $bestScore = $score;
            }
        }

        return $best !== null && $this->looksUsable($best) ? $best : null;
    }

    private function rotatedCopy(string $imagePath, int $degrees, array &$tempFiles): string
    {
        $imagick = new Imagick($imagePath);
        $imagick->rotateImage('white', $degrees);
        $imagick->setImageFormat('png');

        $path = tempnam(sys_get_temp_dir(), 'novix_ocr_rot_').'.png';
        $imagick->writeImage($path);
        $tempFiles[] = $path;

        return $path;
    }

    /** @return string[] Paths to temporary per-page PNGs. */
    private function pdfToImages(string $pdfPath, array &$tempFiles): array
    {
        $imagick = new Imagick;
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);

        $images = [];
        foreach ($imagick as $page) {
            $images[] = $this->cleanedUp($page, $tempFiles, full: false);
        }
        $imagick->clear();

        if ($images === []) {
            throw new RuntimeException('PDF had no pages to read.');
        }

        return $images;
    }

    private function preprocessImage(string $imagePath, array &$tempFiles): string
    {
        $imagick = new Imagick($imagePath);

        return $this->cleanedUp($imagick, $tempFiles, full: true);
    }

    /**
     * Grayscale + contrast boost for everything; despeckle + deskew are
     * skipped for PDF-rasterized pages since a scanner's output is already
     * clean and aligned — those two steps only pay for themselves on noisy,
     * tilted phone-camera photos, and despeckle can otherwise soften small
     * glyphs on an already-crisp scan.
     */
    private function cleanedUp(Imagick $imagick, array &$tempFiles, bool $full): string
    {
        $imagick->setImageColorspace(Imagick::COLORSPACE_GRAY);
        $imagick->normalizeImage();
        $imagick->contrastImage(true);

        if ($full) {
            $imagick->despeckleImage();

            $quantumRange = $imagick->getQuantumRange()['quantumRangeLong'];
            $imagick->deskewImage(0.4 * $quantumRange);
        }

        $imagick->setImageFormat('png');

        $path = tempnam(sys_get_temp_dir(), 'novix_ocr_').'.png';
        $imagick->writeImage($path);
        $tempFiles[] = $path;

        return $path;
    }

    /**
     * Indian lab/clinic reports occasionally mix in Hindi or Gujarati
     * headers, patient details, or stamps alongside the (usually dominant)
     * English text. Tesseract's multi-language mode can read those scripts,
     * but combining dictionaries measurably hurts its accuracy on plain
     * English too — so this is only used as a fallback when English-only
     * comes back unusable, never as the first attempt. Only languages whose
     * trained data is actually installed are requested — asking for a
     * missing one makes Tesseract fail outright, and not every environment
     * (e.g. a local dev machine) has the hin/guj packs installed.
     */
    private static ?string $multiLanguages = null;

    private function multiLanguages(): string
    {
        if (self::$multiLanguages !== null) {
            return self::$multiLanguages;
        }

        $installed = trim(Process::run(['tesseract', '--list-langs'])->output());
        $available = array_map('trim', explode("\n", $installed));

        $wanted = array_values(array_intersect(['eng', 'hin', 'guj'], $available));

        return self::$multiLanguages = $wanted === [] ? 'eng' : implode('+', $wanted);
    }

    /** OEM 1 (LSTM-only) reads faster and at least as accurately as the default combined engine on modern trained data. */
    private function configureTesseract(PendingProcess $pendingProcess, string $imagePath, string $languages): void
    {
        $pendingProcess
            ->timeout(60)
            ->env(['OMP_THREAD_LIMIT' => '1'])
            ->command(['tesseract', $imagePath, 'stdout', '-l', $languages, '--oem', '1', '--psm', '3']);
    }

    private function runTesseract(string $imagePath, string $languages): string
    {
        $result = Process::timeout(60)->env(['OMP_THREAD_LIMIT' => '1'])->run(['tesseract', $imagePath, 'stdout', '-l', $languages, '--oem', '1', '--psm', '3']);

        if ($result->failed()) {
            throw new RuntimeException('Tesseract failed: '.$result->errorOutput());
        }

        return $result->output();
    }
}
