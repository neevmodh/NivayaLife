<?php

namespace Tests\Unit;

use App\Services\Ocr\OcrExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Tesseract loses decimal points below roughly 300 DPI. Measured end to end on
 * a real 900px-wide lab report: every decimal was dropped ("7.8" read as "78",
 * "1.1" as "14") and the pipeline then reported an HbA1c of 78% as fact. The
 * same image upscaled before OCR preserved 9/9 values.
 *
 * These pin the sizing rules, including the memory ceiling — the OCR worker
 * runs with a 96MB limit, so an unbounded upscale would swap a wrong-value bug
 * for an OOM that strands the report in `processing`.
 */
class OcrUpscaleTest extends TestCase
{
    public function test_a_small_image_is_upscaled_towards_the_readable_width(): void
    {
        // The real report that failed.
        [$w, $h] = OcrExtractor::targetOcrDimensions(900, 1150);

        $this->assertSame(2000, $w);
        // Aspect ratio preserved.
        $this->assertSame((int) round(1150 * (2000 / 900)), $h);
    }

    public function test_an_already_large_image_is_left_alone(): void
    {
        $this->assertNull(OcrExtractor::targetOcrDimensions(2250, 2875));
        $this->assertNull(OcrExtractor::targetOcrDimensions(2000, 2600));
    }

    public function test_upscaling_never_exceeds_the_memory_ceiling(): void
    {
        // A wide, short panorama would blow past 8MP if scaled to 2000px wide.
        [$w, $h] = OcrExtractor::targetOcrDimensions(1900, 4000);

        $this->assertLessThanOrEqual(8_000_000, $w * $h);
        // Still an upscale, just a smaller one than the target width implies.
        $this->assertGreaterThan(1900, $w);
    }

    public function test_degenerate_dimensions_are_ignored(): void
    {
        $this->assertNull(OcrExtractor::targetOcrDimensions(0, 0));
        $this->assertNull(OcrExtractor::targetOcrDimensions(-5, 100));
    }
}
