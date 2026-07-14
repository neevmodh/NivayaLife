<?php

namespace App\Services\Ocr;

use Illuminate\Support\Facades\Process;
use Imagick;
use RuntimeException;

/**
 * PDFs are rasterized page-by-page with Imagick (which shells out to
 * Ghostscript for the PDF delegate) before Tesseract ever sees them, since
 * Tesseract only reads raster images. Photos go through a light cleanup pass
 * first — real phone photos of prescriptions are rarely flat and well-lit.
 */
class OcrExtractor
{
    /** @return string Extracted text, empty string if nothing readable was found. */
    public function extract(string $absolutePath, string $mimeType): string
    {
        $tempFiles = [];

        try {
            $pageImages = $mimeType === 'application/pdf'
                ? $this->pdfToImages($absolutePath, $tempFiles)
                : [$this->preprocessImage($absolutePath, $tempFiles)];

            $multiPage = count($pageImages) > 1;
            $pageTexts = [];

            foreach ($pageImages as $index => $imagePath) {
                $text = $this->runTesseract($imagePath);
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

        $alnumCount = preg_match_all('/[A-Za-z0-9]/', $text);

        return $alnumCount >= 15 && ($alnumCount / max(strlen($text), 1)) >= 0.4;
    }

    /** @return string[] Paths to temporary per-page PNGs. */
    private function pdfToImages(string $pdfPath, array &$tempFiles): array
    {
        $imagick = new Imagick;
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);

        $images = [];
        foreach ($imagick as $page) {
            $path = $this->cleanedUp($page, $tempFiles);
            $images[] = $path;
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

        return $this->cleanedUp($imagick, $tempFiles);
    }

    /** Grayscale + contrast boost + deskew + despeckle, written out to a temp PNG. */
    private function cleanedUp(Imagick $imagick, array &$tempFiles): string
    {
        $imagick->setImageColorspace(Imagick::COLORSPACE_GRAY);
        $imagick->normalizeImage();
        $imagick->contrastImage(true);
        $imagick->despeckleImage();

        $quantumRange = $imagick->getQuantumRange()['quantumRangeLong'];
        $imagick->deskewImage(0.4 * $quantumRange);

        $imagick->setImageFormat('png');

        $path = tempnam(sys_get_temp_dir(), 'novix_ocr_').'.png';
        $imagick->writeImage($path);
        $tempFiles[] = $path;

        return $path;
    }

    private function runTesseract(string $imagePath): string
    {
        $result = Process::timeout(60)->run(['tesseract', $imagePath, 'stdout', '--psm', '3']);

        if ($result->failed()) {
            throw new RuntimeException('Tesseract failed: '.$result->errorOutput());
        }

        return $result->output();
    }
}
