<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Gemini's generateContent REST endpoint. Deliberately
 * not a full SDK — this app only ever sends a single text prompt and reads
 * back a single text candidate, so a raw HTTP call is simpler than pulling
 * in a package for the one method we'd actually call.
 */
class GeminiClient
{
    private readonly string $apiKey;

    private readonly string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) config('services.gemini.key');
        $this->model = $model ?? (string) config('services.gemini.model', 'gemini-flash-latest');
    }

    /**
     * @return array{text: string, input_tokens: ?int, output_tokens: ?int}
     */
    public function generate(string $prompt): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $response = Http::timeout(45)
            ->withHeaders(['x-goog-api-key' => $this->apiKey, 'Content-Type' => 'application/json'])
            ->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed: '.$response->status().' '.$response->body());
        }

        $json = $response->json();
        $text = data_get($json, 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            $blockReason = data_get($json, 'promptFeedback.blockReason');
            throw new RuntimeException($blockReason ? "Gemini blocked the request: {$blockReason}" : 'Gemini returned an empty response.');
        }

        return [
            'text' => trim($text),
            'input_tokens' => data_get($json, 'usageMetadata.promptTokenCount'),
            'output_tokens' => data_get($json, 'usageMetadata.candidatesTokenCount'),
        ];
    }
}
