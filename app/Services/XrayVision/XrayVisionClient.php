<?php

namespace App\Services\XrayVision;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the standalone TorchXRayVision service
 * (xray-vision-service/) — a real pretrained chest-X-ray CNN classifier,
 * run separately from this PHP app since it's Python/PyTorch. Optional
 * enrichment only: ProcessReportOcrJob treats any failure here (including
 * "not configured at all") as skippable, never as a reason to fail the
 * report's Gemini-based vision description.
 */
class XrayVisionClient
{
    private readonly ?string $url;

    private readonly ?string $token;

    public function __construct(?string $url = null, ?string $token = null)
    {
        $this->url = $url ?? config('services.xray_vision.url');
        $this->token = $token ?? config('services.xray_vision.token');
    }

    public function isConfigured(): bool
    {
        return filled($this->url);
    }

    /** @return array<int, array{pathology: string, probability: float}> */
    public function analyze(string $absoluteImagePath): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('XRAY_VISION_URL is not configured.');
        }

        $response = $this->request()
            ->attach('file', file_get_contents($absoluteImagePath), basename($absoluteImagePath))
            ->post(rtrim($this->url, '/').'/analyze');

        if ($response->failed()) {
            throw new RuntimeException('X-ray vision service request failed: '.$response->status().' '.$response->body());
        }

        $findings = $response->json('findings');

        if (! is_array($findings)) {
            throw new RuntimeException('X-ray vision service returned an unexpected response shape.');
        }

        return $findings;
    }

    private function request(): PendingRequest
    {
        $request = Http::timeout(20);

        if (filled($this->token)) {
            $request = $request->withHeaders(['X-Service-Token' => $this->token]);
        }

        return $request;
    }
}
