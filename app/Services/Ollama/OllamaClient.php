<?php

namespace App\Services\Ollama;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around a self-hosted Ollama instance — mirrors GeminiClient/
 * GroqClient's generate() shape so callers can try this first and fall back
 * to the existing Gemini→Groq chain unchanged.
 *
 * Uses Ollama's native /api/chat rather than its OpenAI-compatible endpoint,
 * because the latter has nowhere to pass `options` — and `options.num_thread`
 * turned out to be the difference between 1.5 and 48 tokens/sec in a
 * container (see generate()).
 */
class OllamaClient
{
    private readonly string $url;

    private readonly string $chatModel;

    private readonly string $embedModel;

    private readonly ?int $numThread;

    public function __construct(?string $url = null, ?string $chatModel = null, ?string $embedModel = null, ?int $numThread = null)
    {
        $this->url = rtrim($url ?? (string) config('services.ollama.url'), '/');
        $this->chatModel = $chatModel ?? (string) config('services.ollama.chat_model', 'qwen2.5:3b-instruct');
        $this->embedModel = $embedModel ?? (string) config('services.ollama.embed_model', 'nomic-embed-text');
        $this->numThread = $numThread ?? (config('services.ollama.num_thread') ? (int) config('services.ollama.num_thread') : null);
    }

    public function isConfigured(): bool
    {
        return $this->url !== '';
    }

    /**
     * @param  float|null  $temperature  Pass 0 for extraction-style calls where
     *                                   the same document should yield the same
     *                                   structure every time. Null leaves the
     *                                   model's own default in place.
     * @param  int  $timeout  Seconds. The 60s default suits the assistant,
     *                        which runs inside a user's HTTP request. Queued
     *                        work should pass more: a shared CPU generating a
     *                        few hundred structured tokens is far slower than
     *                        a dev machine, and this timeout was silently
     *                        forcing production extraction onto the paid
     *                        fallback.
     * @return array{text: string, input_tokens: ?int, output_tokens: ?int}
     */
    public function generate(string $prompt, ?float $temperature = null, int $timeout = 60): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OLLAMA_URL is not configured.');
        }

        $options = [];

        if ($temperature !== null) {
            $options['temperature'] = $temperature;
        }

        // Ollama sizes its thread pool from nproc, which inside a container
        // reports the HOST's core count, not the cgroup quota. On Railway that
        // meant ~48 threads fighting over an 8-CPU quota: measured 165s and
        // 1.5 tok/s, versus 6.4s and 48 tok/s once threads matched the quota.
        // Left unset locally, where Ollama's own detection is already correct.
        if ($this->numThread !== null) {
            $options['num_thread'] = $this->numThread;
        }

        // The native endpoint is used rather than the OpenAI-compatible one
        // purely because /v1/chat/completions has nowhere to put `options`.
        $response = Http::timeout($timeout)->post("{$this->url}/api/chat", [
            'model' => $this->chatModel,
            'stream' => false,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'options' => (object) $options,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Ollama request failed: '.$response->body());
        }

        $json = $response->json();

        return [
            'text' => trim($json['message']['content'] ?? ''),
            'input_tokens' => $json['prompt_eval_count'] ?? null,
            'output_tokens' => $json['eval_count'] ?? null,
        ];
    }

    /** @return float[] */
    public function embed(string $text): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OLLAMA_URL is not configured.');
        }

        $response = Http::timeout(30)
            ->post("{$this->url}/api/embeddings", [
                'model' => $this->embedModel,
                'prompt' => $text,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Ollama embeddings request failed: '.$response->body());
        }

        return $response->json('embedding') ?? [];
    }
}
