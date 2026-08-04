<?php

namespace App\Services\Railway;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Railway's public GraphQL API. The only operation this
 * app needs is "deploy whatever the latest commit on the connected branch
 * is" — the same thing Railway's own dashboard command palette calls
 * "Deploy Latest Commit" — so that's the only mutation implemented here.
 */
class RailwayClient
{
    private const ENDPOINT = 'https://backboard.railway.com/graphql/v2';

    private readonly ?string $apiToken;

    private readonly ?string $projectId;

    private readonly ?string $serviceId;

    private readonly ?string $environmentId;

    public function __construct()
    {
        $this->apiToken = config('services.railway.api_token');
        $this->projectId = config('services.railway.project_id');
        $this->serviceId = config('services.railway.service_id');
        $this->environmentId = config('services.railway.environment_id');
    }

    public function deployLatestCommit(): void
    {
        if (! $this->apiToken || ! $this->projectId || ! $this->serviceId || ! $this->environmentId) {
            throw new RuntimeException('Railway is not fully configured (RAILWAY_API_TOKEN/PROJECT_ID/SERVICE_ID/ENVIRONMENT_ID).');
        }

        $response = Http::timeout(30)
            ->withToken($this->apiToken)
            ->post(self::ENDPOINT, [
                'query' => 'mutation($environmentId: String!, $projectId: String!, $serviceId: String!) {
                    environmentTriggersDeploy(input: {environmentId: $environmentId, projectId: $projectId, serviceId: $serviceId})
                }',
                'variables' => [
                    'environmentId' => $this->environmentId,
                    'projectId' => $this->projectId,
                    'serviceId' => $this->serviceId,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Railway deploy request failed: '.$response->status().' '.$response->body());
        }

        $json = $response->json();

        if (! empty($json['errors'])) {
            throw new RuntimeException('Railway deploy request returned errors: '.json_encode($json['errors']));
        }

        if (($json['data']['environmentTriggersDeploy'] ?? null) !== true) {
            throw new RuntimeException('Railway deploy request did not confirm success: '.$response->body());
        }
    }
}
