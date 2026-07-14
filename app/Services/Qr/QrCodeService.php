<?php

namespace App\Services\Qr;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Single place every feature goes through to produce a QR code, so the
 * Emergency Card and Report Sharing features never each roll their own.
 * Codes always encode a plain URL string — not JSON — so any generic phone
 * camera or QR app can scan and open the link directly.
 *
 * SVG is used for on-screen display (crisp at any size). PNG is used for
 * PDF embedding — dompdf's inline-SVG support is unreliable, PNG renders
 * correctly every time.
 */
class QrCodeService
{
    /** Raw SVG markup, ready to embed inline or store. */
    public function svg(string $data, int $size = 300): string
    {
        return QrCode::format('svg')->size($size)->margin(1)->generate($data);
    }

    /** Raw PNG bytes — used where SVG isn't reliably supported (PDF embedding). */
    public function png(string $data, int $size = 300): string
    {
        return QrCode::format('png')->size($size)->margin(1)->generate($data);
    }

    /** Generates and stores an SVG QR code on the private disk, returning its path. */
    public function store(string $data, string $directory, int $size = 300): string
    {
        $path = trim($directory, '/').'/qr-'.Str::lower(Str::random(20)).'.svg';
        Storage::disk('local')->put($path, $this->svg($data, $size));

        return $path;
    }

    /** A base64 data: URI for direct use in <img src> in HTML pages. */
    public function dataUri(string $data, int $size = 300): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($data, $size));
    }

    /** PNG data: URI, for embedding in PDFs specifically. */
    public function pngDataUri(string $data, int $size = 300): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($data, $size));
    }

    /** Same as dataUri() but reads an SVG QR code already stored on disk, e.g. id_cards.qr_code_path. */
    public function dataUriFromPath(string $path): ?string
    {
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
