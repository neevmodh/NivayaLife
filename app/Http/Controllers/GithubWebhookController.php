<?php

namespace App\Http\Controllers;

use App\Models\PendingDeployment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Unauthenticated by design (GitHub can't log in) — trust is established via
 * the X-Hub-Signature-256 HMAC instead, matching how every provider's
 * webhooks are conventionally secured.
 */
class GithubWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $secret = config('services.github.webhook_secret');

        if (! $secret || ! $this->hasValidSignature($request, $secret)) {
            return response('Invalid signature.', 401);
        }

        if ($request->header('X-GitHub-Event') !== 'push') {
            return response('Ignored: not a push event.', 200);
        }

        $payload = $request->json()->all();

        if (($payload['ref'] ?? null) !== 'refs/heads/main' || empty($payload['head_commit'])) {
            return response('Ignored: not a push to main.', 200);
        }

        $commit = $payload['head_commit'];

        PendingDeployment::recordPush([
            'commit_sha' => $commit['id'],
            'commit_message' => $commit['message'] ?? '',
            'author_name' => $commit['author']['name'] ?? 'Unknown',
            'author_email' => $commit['author']['email'] ?? null,
            'branch' => 'main',
            'pushed_at' => $commit['timestamp'] ?? now(),
            'status' => 'pending',
        ]);

        return response('OK', 200);
    }

    private function hasValidSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
