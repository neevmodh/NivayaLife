<?php

namespace App\Services\Gemini;

use App\Models\AiJob;
use App\Models\ChatMessage;

/**
 * Tracks usage against Gemini's free-tier daily request cap. Only calls that
 * actually hit Gemini count toward it — ai_jobs rows of type 'ocr' run
 * locally through Tesseract and never touch the API, so they're excluded.
 */
class GeminiQuota
{
    /** Stop dispatching new Gemini calls once usage crosses this fraction of the daily limit, leaving headroom for in-flight jobs. */
    private const SAFE_FRACTION = 0.9;

    public function usedToday(): int
    {
        $reportCalls = AiJob::where('job_type', '!=', 'ocr')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        // output_tokens is only ever set after a real Gemini response — the
        // "I'm near today's limit" bounce message and the on-error fallback
        // text are both saved as assistant messages too (so the thread makes
        // sense on reload) but never called Gemini, and must NOT count here.
        // Counting them would be self-reinforcing: once near the limit,
        // every bounced request would inflate the count further and the
        // quota could never recover before the daily reset.
        $chatCalls = ChatMessage::where('role', 'assistant')
            ->whereNotNull('output_tokens')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $reportCalls + $chatCalls;
    }

    public function dailyLimit(): int
    {
        return (int) config('services.gemini.daily_limit', 1500);
    }

    public function isNearLimit(): bool
    {
        return $this->usedToday() >= (int) ($this->dailyLimit() * self::SAFE_FRACTION);
    }

    /** Seconds until the quota resets (midnight UTC, when "today" rolls over). */
    public function secondsUntilReset(): int
    {
        return max(60, now()->diffInSeconds(now()->endOfDay()->addSecond()));
    }
}
