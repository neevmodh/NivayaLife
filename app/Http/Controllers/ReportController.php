<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ResolvesActiveFamilyMember;

    public function index(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);

        $reports = Report::where('family_member_id', $active->id)
            ->where('is_archived', false)
            ->orderByDesc('uploaded_at')
            ->get();

        return view('reports.index', [
            'active' => $active,
            'reports' => $reports,
        ]);
    }

    public function show(Request $request, Report $report): View
    {
        $user = $request->user();
        $this->authorizeView($report, $user);

        $report->load(['familyMember', 'healthMetrics', 'aiResponses' => fn ($q) => $q->orderByDesc('generated_at')]);

        // response_type 'summary' covers both the automatic short summary and
        // every on-demand detailed explanation (there's no separate enum
        // value) — anything that isn't the current ai_summary cache is a
        // detailed explanation from the history.
        $detailedExplanations = $report->aiResponses
            ->where('response_type', 'summary')
            ->where('language', 'en')
            ->reject(fn ($r) => $r->content === $report->ai_summary)
            ->map(fn ($r) => ['content' => $r->content, 'generated_at' => $r->generated_at?->toIso8601String()])
            ->values();

        $translations = $report->aiResponses
            ->where('response_type', 'translation')
            ->groupBy('language')
            ->map(fn ($group) => $group->sortByDesc('generated_at')->first()->content);

        return view('reports.show', [
            'report' => $report,
            'canEdit' => $report->familyMember->canBeEditedBy($user),
            'detailedExplanations' => $detailedExplanations,
            'translations' => $translations,
        ]);
    }

    /** Lightweight polling endpoint the detail page uses to reflect OCR/summary progress live, without a full reload. */
    public function status(Request $request, Report $report): JsonResponse
    {
        $this->authorizeView($report, $request->user());

        $summaryJobFailed = $report->aiJobs()
            ->where('job_type', 'summary')
            ->where('status', 'failed')
            ->exists();

        return response()->json([
            'ocr_status' => $report->ocr_status,
            'ocr_text' => $report->ocr_text,
            'ai_summary' => $report->ai_summary,
            'ai_summary_generated_at' => $report->ai_summary_generated_at?->toIso8601String(),
            'summary_job_failed' => $summaryJobFailed,
        ]);
    }

    /** A human correction is more trustworthy than the OCR guess, so it overwrites ocr_text directly. */
    public function updateOcrText(Request $request, Report $report): JsonResponse
    {
        $user = $request->user();
        abort_unless($report->familyMember->canBeEditedBy($user), 403);

        $validated = $request->validate([
            'ocr_text' => ['required', 'string'],
        ]);

        $report->update(['ocr_text' => $validated['ocr_text']]);

        return response()->json(['success' => true]);
    }

    public function file(Request $request, Report $report): StreamedResponse
    {
        $this->authorizeView($report, $request->user());

        abort_unless(Storage::disk('local')->exists($report->file_path), 404);

        return Storage::disk('local')->response($report->file_path, $report->original_filename);
    }

    private function authorizeView(Report $report, $user): void
    {
        abort_unless($report->familyMember->hasGrantedAccessTo($user), 403);
    }
}
