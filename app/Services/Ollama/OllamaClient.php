<?php

namespace App\Services\Ollama;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around a self-hosted Ollama instance — mirrors GeminiClient/
 * GroqClient's generate() shape so AiChatController can try this first and
 * fall back to the existing Gemini→Groq chain unchanged. Ollama's chat
 * endpoint is OpenAI-compatible, so this is nearly identical to GroqClient.
 */
class OllamaClient
{
    private readonly string $url;

    private readonly string $chatModel;

    private readonly string $embedModel;

    public function __construct(?string $url = null, ?string $chatModel = null, ?string $embedModel = null)
    {
        $this->url = rtrim($url ?? (string) config('services.ollama.url'), '/');
        $this->chatModel = $chatModel ?? (string) config('services.ollama.chat_model', 'qwen2.5:3b-instruct');
        $this->embedModel = $embedModel ?? (string) config('services.ollama.embed_model', 'nomic-embed-text');
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
     * @return array{text: string, input_tokens: ?int, output_tokens: ?int}
     */
    public function generate(string $prompt, ?float $temperature = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OLLAMA_URL is not configured.');
        }

        $payload = [
            'model' => $this->chatModel,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        $response = Http::timeout(60)->post("{$this->url}/v1/chat/completions", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Ollama request failed: '.$response->body());
        }

        $json = $response->json();

        return [
            'text' => trim($json['choices'][0]['message']['content'] ?? ''),
            'input_tokens' => $json['usage']['prompt_tokens'] ?? null,
            'output_tokens' => $json['usage']['completion_tokens'] ?? null,
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
