<?php

namespace App\Services\Groq;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Groq's OpenAI-compatible chat completions endpoint —
 * mirrors GeminiClient's generate() shape so AiClient can treat either
 * provider identically. Used as the last resort in the fallback chain, once
 * every Gemini key has failed.
 */
class GroqClient
{
    private readonly string $apiKey;

    private readonly string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.groq.key');
        $this->model = $model ?? (string) config('services.groq.model', 'qwen/qwen3.8-27b');
    }

    /**
     * @return array{text: string, input_tokens: ?int, output_tokens: ?int}
     */
    public function generate(string $prompt): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('Groq API key is not configured.');
        }

        $response = Http::timeout(45)
            ->withToken($this->apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq request failed: '.$response->status().' '.$response->body());
        }

        $json = $response->json();
        $text = data_get($json, 'choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Groq returned an empty response.');
        }

        return [
            'text' => trim($text),
            'input_tokens' => data_get($json, 'usage.prompt_tokens'),
            'output_tokens' => data_get($json, 'usage.completion_tokens'),
        ];
    }
}
