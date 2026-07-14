<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateShortSummaryJob;
use App\Models\AiJob;
use App\Models\Report;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiQuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Every action here is user-initiated and calls Gemini synchronously — no
 * queueing, since the user is actively waiting on a button click. Both
 * actions spend the family's shared daily Gemini quota, so both are gated
 * the same way as editing (canBeEditedBy), not just viewing.
 */
class ReportAiController extends Controller
{
    private const LANGUAGE_NAMES = ['hi' => 'Hindi', 'gu' => 'Gujarati'];

    private const MAX_OCR_CHARS = 8000;

    public function detailedExplanation(Request $request, Report $report, GeminiClient $gemini, GeminiQuota $quota): JsonResponse
    {
        $user = $request->user();
        abort_unless($report->familyMember->canBeEditedBy($user), 403);

        if (! $report->ocr_text || $report->ocr_status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'This report has no readable text to explain yet.'], 422);
        }

        if ($quota->isNearLimit()) {
            return response()->json(['success' => false, 'message' => "We're close to today's AI usage limit — please try again in a little while."], 429);
        }

        $aiJob = AiJob::create([
            'report_id' => $report->id,
            'job_type' => 'summary',
            'status' => 'running',
            'provider' => 'gemini',
            'started_at' => now(),
        ]);

        try {
            $result = $gemini->generate($this->buildDetailedPrompt($report));
            $content = $result['text']."\n\n".GenerateShortSummaryJob::DISCLAIMER;

            $response = $report->recordAiResponse('summary', $content, 'en', $aiJob, updateSummaryCache: false);

            $aiJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ]);

            return response()->json(['success' => true, 'content' => $content, 'generated_at' => $response->generated_at->toIso8601String()]);
        } catch (Throwable $e) {
            report($e);

            $aiJob->update(['status' => 'failed', 'completed_at' => now(), 'error_message' => Str::limit($e->getMessage(), 500)]);

            return response()->json(['success' => false, 'message' => 'Could not generate an explanation right now — please try again.'], 500);
        }
    }

    /**
     * The automatic short summary only ever runs once (dispatched right
     * after OCR completes) — if that single attempt hits a transient Gemini
     * failure (the free-tier API does occasionally 503/timeout), there was
     * previously no way back short of an owner manually re-dispatching the
     * job. This lets the user themselves trigger exactly the same job again.
     */
    public function retrySummary(Request $request, Report $report, GeminiQuota $quota): JsonResponse
    {
        $user = $request->user();
        abort_unless($report->familyMember->canBeEditedBy($user), 403);

        if ($report->ocr_status !== 'completed' || ! $report->ocr_text) {
            return response()->json(['success' => false, 'message' => 'This report has no readable text to summarize yet.'], 422);
        }

        if ($report->ai_summary) {
            return response()->json(['success' => false, 'message' => 'This report already has a summary.'], 422);
        }

        if ($quota->isNearLimit()) {
            return response()->json(['success' => false, 'message' => "We're close to today's AI usage limit — please try again in a little while."], 429);
        }

        GenerateShortSummaryJob::dispatch($report);

        return response()->json(['success' => true]);
    }

    public function translate(Request $request, Report $report, GeminiClient $gemini, GeminiQuota $quota): JsonResponse
    {
        $user = $request->user();
        abort_unless($report->familyMember->canBeEditedBy($user), 403);

        $validated = $request->validate(['language' => ['required', 'in:hi,gu']]);
        $language = $validated['language'];

        if (! $report->ai_summary) {
            return response()->json(['success' => false, 'message' => 'No summary to translate yet.'], 422);
        }

        // Cached: never re-call the API for a language already translated on this report.
        $cached = $report->aiResponses()
            ->where('response_type', 'translation')
            ->where('language', $language)
            ->latest('generated_at')
            ->first();

        if ($cached) {
            return response()->json(['success' => true, 'content' => $cached->content, 'cached' => true]);
        }

        if ($quota->isNearLimit()) {
            return response()->json(['success' => false, 'message' => "We're close to today's AI usage limit — please try again in a little while."], 429);
        }

        $aiJob = AiJob::create([
            'report_id' => $report->id,
            'job_type' => 'translation',
            'status' => 'running',
            'provider' => 'gemini',
            'started_at' => now(),
        ]);

        try {
            $languageName = self::LANGUAGE_NAMES[$language];
            $prompt = "Translate the following medical report summary into {$languageName}. Preserve all names, numbers, and medical terms accurately. Keep it natural, plain, and concise. Reply with only the translation, no extra commentary.\n\nTEXT:\n{$report->ai_summary}";

            $result = $gemini->generate($prompt);

            $response = $report->recordAiResponse('translation', $result['text'], $language, $aiJob);

            $aiJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ]);

            return response()->json(['success' => true, 'content' => $response->content, 'cached' => false]);
        } catch (Throwable $e) {
            report($e);

            $aiJob->update(['status' => 'failed', 'completed_at' => now(), 'error_message' => Str::limit($e->getMessage(), 500)]);

            return response()->json(['success' => false, 'message' => 'Could not translate this summary right now — please try again.'], 500);
        }
    }

    private function buildDetailedPrompt(Report $report): string
    {
        $ocrText = Str::limit($report->ocr_text, self::MAX_OCR_CHARS, '');

        return <<<PROMPT
        You are providing a fuller, plain-language explanation of a {$report->typeLabel()} for someone using a personal family health-record app, to help them understand their own report before discussing it with a doctor. Below is text extracted via OCR from the document — it may contain minor OCR errors.

        Explain:
        1. What this report is checking for.
        2. A plain-language explanation of each value or finding mentioned.
        3. Which values, if any, appear to be outside the normal range.
        4. A few questions they might want to ask their doctor about this report.

        Frame point 4 as suggestions to discuss with a doctor — never as instructions to follow or a diagnosis. Write in clear, friendly, plain language. Do not use markdown formatting. Do not include a disclaimer — one is appended separately.

        OCR TEXT:
        {$ocrText}
        PROMPT;
    }
}
