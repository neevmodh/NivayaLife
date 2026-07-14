<?php

namespace App\Services\Gemini;

use App\Models\AiJob;

/**
 * Tracks usage against Gemini's free-tier daily request cap. Only ai_jobs
 * rows that actually call Gemini count toward it — OCR runs locally through
 * Tesseract and never touches the API, so 'ocr' rows are excluded.
 */
class GeminiQuota
{
    /** Stop dispatching new Gemini calls once usage crosses this fraction of the daily limit, leaving headroom for in-flight jobs. */
    private const SAFE_FRACTION = 0.9;

    public function usedToday(): int
    {
        return AiJob::where('job_type', '!=', 'ocr')
            ->whereDate('created_at', now()->toDateString())
            ->count();
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
