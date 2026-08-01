<?php

namespace App\Services\ClinicalNlp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the standalone clinical-nlp-service/ (PaddleOCR +
 * scispaCy/medspaCy) — same "optional enrichment, never a dependency" style
 * as XrayVisionClient. Callers must treat any failure (including "not
 * configured at all") as skippable.
 */
class ClinicalNlpClient
{
    private readonly ?string $url;

    private readonly ?string $token;

    public function __construct(?string $url = null, ?string $token = null)
    {
        $this->url = $url ?? config('services.clinical_nlp.url');
        $this->token = $token ?? config('services.clinical_nlp.token');
    }

    public function isConfigured(): bool
    {
        return filled($this->url);
    }

    /**
     * @param  array<int, array{data: string, mimeType: string}>  $images
     * @return array{text: string, looks_usable: bool}
     */
    public function ocr(array $images): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('CLINICAL_NLP_URL is not configured.');
        }

        $response = $this->request()->post(rtrim($this->url, '/').'/ocr', ['images' => $images]);

        if ($response->failed()) {
            throw new RuntimeException('Clinical NLP OCR request failed: '.$response->status().' '.$response->body());
        }

        $text = $response->json('text');
        $looksUsable = $response->json('looks_usable');

        if (! is_string($text) || ! is_bool($looksUsable)) {
            throw new RuntimeException('Clinical NLP service returned an unexpected /ocr response shape.');
        }

        return ['text' => $text, 'looks_usable' => $looksUsable];
    }

    /** @return array<int, array{text: string, label: string, negated: bool}> */
    public function extractEntities(string $text): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('CLINICAL_NLP_URL is not configured.');
        }

        $response = $this->request()->post(rtrim($this->url, '/').'/extract-entities', ['text' => $text]);

        if ($response->failed()) {
            throw new RuntimeException('Clinical NLP entity extraction failed: '.$response->status().' '.$response->body());
        }

        $entities = $response->json('entities');

        if (! is_array($entities)) {
            throw new RuntimeException('Clinical NLP service returned an unexpected /extract-entities response shape.');
        }

        return $entities;
    }

    private function request(): PendingRequest
    {
        $request = Http::timeout(30);

        if (filled($this->token)) {
            $request = $request->withHeaders(['X-Service-Token' => $this->token]);
        }

        return $request;
    }
}
