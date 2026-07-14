<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Http\Response;

/**
 * Single place every feature renders a PDF through, so the Emergency Card
 * and Report Sharing features never each configure dompdf differently.
 * Every PDF in the app is a real Blade view rendered to paper — never a
 * screenshot or a static mockup image.
 *
 * $paper accepts either a named size ('a4') or an explicit
 * [x1, y1, x2, y2] box in points (72pt = 1 inch), e.g. for the wallet-sized
 * emergency card. When an explicit box is given, pass it already in the
 * final desired orientation and leave $orientation as 'portrait' — dompdf
 * swaps width/height itself for 'landscape', which double-applies otherwise.
 */
class PdfExportService
{
    public function make(string $view, array $data = [], string|array $paper = 'a4', string $orientation = 'portrait'): PdfDocument
    {
        // setPaper() must run BEFORE loadView() — calling it after silently
        // produces a spurious blank second page with a custom [x,y,x,y] box
        // (verified against both this wrapper and raw Dompdf; named sizes
        // like 'a4' aren't affected either way, but this order is safe for both).
        return PdfFacade::setPaper($paper, $orientation)->loadView($view, $data);
    }

    public function stream(string $view, array $data, string $filename, string|array $paper = 'a4', string $orientation = 'portrait'): Response
    {
        return $this->make($view, $data, $paper, $orientation)->stream($filename);
    }

    public function download(string $view, array $data, string $filename, string|array $paper = 'a4', string $orientation = 'portrait'): Response
    {
        return $this->make($view, $data, $paper, $orientation)->download($filename);
    }
}
