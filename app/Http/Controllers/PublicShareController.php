<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Report;
use App\Models\Share;
use App\Services\Pdf\PdfExportService;
use App\Support\TempFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Imagick;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Fully public, token-based, no account needed. Every branch here is
 * read-only except recordSuccessfulView()'s bookkeeping (view_count,
 * first_viewed_at, and — for one-time shares — the auto-revocation).
 */
class PublicShareController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $share = Share::where('token', $token)->firstOrFail();

        if ($unavailable = $this->unavailableView($share)) {
            return $unavailable;
        }

        if ($share->requiresPin() && ! $share->isUnlockedInSession()) {
            return view('shares.public.pin', ['share' => $share, 'error' => null]);
        }

        // For PIN shares, verifyPin() already recorded this unlock episode's
        // view — recording it again here on every subsequent page load would
        // double count a single "episode" of access.
        if (! $share->requiresPin()) {
            $share->recordSuccessfulView();
            AuditLog::record('share_viewed', 'Share', $share->id, null, $share->family_member_id);
            $share->refresh();
        }

        return view('shares.public.show', $this->shareData($share));
    }

    public function verifyPin(Request $request, string $token): View|RedirectResponse
    {
        $share = Share::where('token', $token)->firstOrFail();
        abort_unless($share->requiresPin(), 404);

        if ($unavailable = $this->unavailableView($share)) {
            return $unavailable;
        }

        $validated = $request->validate(['pin' => ['required', 'digits:4']]);

        if (! $share->verifyPin($validated['pin'])) {
            return view('shares.public.pin', ['share' => $share, 'error' => 'Incorrect PIN — please try again.']);
        }

        $share->recordSuccessfulView();
        AuditLog::record('share_viewed', 'Share', $share->id, null, $share->family_member_id);

        return redirect()->route('share.public.show', $token);
    }

    public function pdf(Request $request, string $token, PdfExportService $pdf): Response
    {
        $share = Share::where('token', $token)->firstOrFail();

        abort_unless($share->isAccessibleNow(), 404);
        abort_if($share->requiresPin() && ! $share->isUnlockedInSession(), 403);

        $data = $this->shareData($share);
        $data['previewDataUris'] = $data['reports']->mapWithKeys(
            fn ($report) => [$report->id => $this->reportPreviewDataUri($report)]
        );

        return $pdf->download(
            'shares.public.pdf',
            $data,
            "shared-report-{$share->token}.pdf",
        );
    }

    /**
     * Base64-embeds the report's original file/image for the PDF. Images
     * embed directly; a source PDF's first page is rasterized to an image
     * first, since a PDF can't be embedded inside another PDF as a page.
     */
    private function reportPreviewDataUri(Report $report): ?string
    {
        if (! Storage::disk('local')->exists($report->file_path)) {
            return null;
        }

        try {
            if (str_starts_with($report->mime_type ?? '', 'image/')) {
                return 'data:'.$report->mime_type.';base64,'.base64_encode(Storage::disk('local')->get($report->file_path));
            }

            if ($report->mime_type === 'application/pdf') {
                return TempFile::fromDisk('local', $report->file_path, function (string $absolutePath) {
                    $imagick = new Imagick;
                    $imagick->setResolution(120, 120);
                    $imagick->readImage($absolutePath.'[0]');
                    $imagick->setImageFormat('png');

                    return 'data:image/png;base64,'.base64_encode($imagick->getImageBlob());
                });
            }
        } catch (Throwable $e) {
            report($e);
        }

        return null;
    }

    /** Reports' normal file route requires an authenticated, authorized user — a public share visitor has neither, so this checks membership in the share's own bundle instead. */
    public function file(Request $request, string $token, Report $report): StreamedResponse
    {
        $share = Share::where('token', $token)->firstOrFail();

        abort_unless($share->isAccessibleNow(), 404);
        abort_if($share->requiresPin() && ! $share->isUnlockedInSession(), 403);
        abort_unless($this->reportBelongsToShare($share, $report), 403);

        abort_unless(Storage::disk('local')->exists($report->file_path), 404);

        return Storage::disk('local')->response($report->file_path, $report->original_filename);
    }

    private function reportBelongsToShare(Share $share, Report $report): bool
    {
        if ($share->access_type === 'single_report') {
            return $share->report_id === $report->id;
        }

        return $report->family_member_id === $share->family_member_id;
    }

    private function unavailableView(Share $share): ?View
    {
        if ($share->isExpired()) {
            return view('shares.public.unavailable', ['reason' => 'expired']);
        }

        if (! $share->isAccessibleNow()) {
            return view('shares.public.unavailable', ['reason' => $share->is_one_time ? 'used' : 'revoked']);
        }

        return null;
    }

    private function shareData(Share $share): array
    {
        $familyMember = $share->familyMember;

        $reports = $share->access_type === 'single_report'
            ? Report::with('aiResponses')->where('id', $share->report_id)->get()
            : $familyMember->reports()->where('is_archived', false)->with('aiResponses')->orderByDesc('uploaded_at')->get();

        // Latest detailed explanation (if any) alongside each report, keyed by report id.
        $detailedExplanations = $reports->mapWithKeys(function ($report) {
            $latest = $report->aiResponses
                ->where('response_type', 'summary')
                ->where('language', 'en')
                ->reject(fn ($r) => $r->content === $report->ai_summary)
                ->sortByDesc('generated_at')
                ->first();

            return [$report->id => $latest?->content];
        });

        return [
            'share' => $share,
            'familyMember' => $familyMember,
            'reports' => $reports,
            'detailedExplanations' => $detailedExplanations,
        ];
    }
}
