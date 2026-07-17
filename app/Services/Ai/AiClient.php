<?php

namespace App\Services\Ai;

use App\Services\Gemini\GeminiClient;
use App\Services\Groq\GroqClient;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/**
 * Tries each configured AI credential in order — up to three Gemini API
 * keys, then Groq as a last resort — so one key hitting its free-tier daily
 * limit (or a transient provider outage, which the free-tier Gemini API
 * does hit occasionally) doesn't take the whole app's AI features down.
 *
 * A credential that just failed is skipped for a cooldown window rather
 * than retried on every single call — a genuinely exhausted key shouldn't
 * cost a network round-trip on every request for the rest of the day. The
 * cooldown is only ever a soft skip though: if every credential is in
 * cooldown, the whole chain is tried again anyway on a second pass rather
 * than giving up, since a cooldown is a guess based on a past failure, not
 * a guarantee that a key is still down now.
 */
class AiClient
{
    private const COOLDOWN_MINUTES = 15;

    /** @return array{text: string, input_tokens: ?int, output_tokens: ?int, provider: string} */
    public function generate(string $prompt): array
    {
        $credentials = $this->credentials();

        if ($credentials === []) {
            throw new RuntimeException('No AI provider is configured.');
        }

        $lastException = null;

        foreach ([true, false] as $respectCooldown) {
            foreach ($credentials as $index => $credential) {
                if ($respectCooldown && Cache::has($this->cooldownKey($index))) {
                    continue;
                }

                try {
                    $result = $this->call($credential, $prompt);
                    Cache::forget($this->cooldownKey($index));

                    return $result + ['provider' => $credential['provider']];
                } catch (Throwable $e) {
                    $lastException = $e;
                    Cache::put($this->cooldownKey($index), true, now()->addMinutes(self::COOLDOWN_MINUTES));
                }
            }
        }

        throw $lastException;
    }

    /**
     * Whether there's any credential configured at all. Cooldown state
     * deliberately isn't checked here — generate() always makes a real
     * attempt even when every credential is in cooldown, so "in cooldown"
     * never actually means "don't bother."
     */
    public function hasAvailableCredential(): bool
    {
        return $this->credentials() !== [];
    }

    /** @return array{text: string, input_tokens: ?int, output_tokens: ?int} */
    private function call(array $credential, string $prompt): array
    {
        return match ($credential['provider']) {
            'gemini' => (new GeminiClient($credential['key'], $credential['model']))->generate($prompt),
            'groq' => (new GroqClient($credential['key'], $credential['model']))->generate($prompt),
        };
    }

    /** @return array<int, array{provider: string, key: string, model: ?string}> */
    private function credentials(): array
    {
        $chain = [];

        foreach (['key', 'key_2', 'key_3'] as $field) {
            if ($key = config("services.gemini.{$field}")) {
                $chain[] = ['provider' => 'gemini', 'key' => $key, 'model' => config('services.gemini.model')];
            }
        }

        if ($key = config('services.groq.key')) {
            $chain[] = ['provider' => 'groq', 'key' => $key, 'model' => config('services.groq.model')];
        }

        return $chain;
    }

    private function cooldownKey(int $index): string
    {
        return "ai_credential_cooldown:{$index}";
    }
}
