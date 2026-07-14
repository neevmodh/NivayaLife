<?php

namespace App\Http\Controllers;

use App\Models\IdCard;
use App\Services\Pdf\PdfExportService;
use App\Services\Qr\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Fully public — no auth, no login prompt. A first responder scanning a QR
 * code off a wallet card or phone case has no account and needs the page to
 * just load. Every view here is intentionally read-only: nothing on this
 * controller can ever change data.
 */
class EmergencyCardController extends Controller
{
    public function show(Request $request, string $cardNumber): View
    {
        $card = IdCard::where('card_number', $cardNumber)->first();

        if (! $card || ! $card->is_active) {
            return view('emergency.inactive', ['labels' => config('emergency_card')]);
        }

        return view('emergency.show', $this->cardData($card));
    }

    public function pdfWallet(Request $request, string $cardNumber, PdfExportService $pdf): Response
    {
        $card = $this->activeCardOrAbort($cardNumber);

        // ID-1 card size (85.6mm x 54mm) converted to points, already in the
        // final landscape shape — orientation stays 'portrait' so dompdf
        // doesn't swap these dimensions again.
        return $pdf->download(
            'emergency.pdf-wallet',
            $this->cardData($card),
            "emergency-card-wallet-{$card->card_number}.pdf",
            [0, 0, 242.65, 153.07],
        );
    }

    public function pdfFull(Request $request, string $cardNumber, PdfExportService $pdf): Response
    {
        $card = $this->activeCardOrAbort($cardNumber);

        return $pdf->download(
            'emergency.pdf-full',
            $this->cardData($card),
            "emergency-card-{$card->card_number}.pdf",
        );
    }

    private function activeCardOrAbort(string $cardNumber): IdCard
    {
        $card = IdCard::where('card_number', $cardNumber)->first();
        abort_unless($card && $card->is_active, 404);

        return $card;
    }

    private function cardData(IdCard $card): array
    {
        $member = $card->familyMember;

        $allergies = $member->allergies()
            ->orderByRaw("FIELD(severity, 'severe', 'moderate', 'mild')")
            ->get();

        $conditions = $member->chronicConditions()->where('status', 'active')->get();
        $medications = $member->medications()->where('active', true)->get();
        $doctor = $member->doctors()->oldest()->first();

        $qrService = app(QrCodeService::class);

        return [
            'card' => $card,
            'member' => $member,
            'allergies' => $allergies,
            'conditions' => $conditions,
            'medications' => $medications,
            'doctor' => $doctor,
            'qrDataUri' => $qrService->dataUriFromPath($card->qr_code_path),
            'qrPngDataUri' => $qrService->pngDataUri(url('/emergency/'.$card->card_number)),
            'photoDataUri' => $this->photoDataUri($member->photo_path),
            'labels' => config('emergency_card'),
        ];
    }

    /** Base64-embedded so dompdf never needs filesystem/chroot access to render the photo. */
    private function photoDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return "data:{$mime};base64,".base64_encode(Storage::disk('public')->get($path));
    }
}
