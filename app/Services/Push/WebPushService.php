<?php

namespace App\Services\Push;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Thin wrapper around minishlink/web-push. Purely additive to the existing
 * Mail-based reminders — every call site keeps sending its email regardless
 * of what happens here, so a misconfigured or unreachable push endpoint
 * never blocks a reminder from reaching someone.
 */
class WebPushService
{
    public function isConfigured(): bool
    {
        return filled(config('services.vapid.public_key')) && filled(config('services.vapid.private_key'));
    }

    /**
     * Sends to every subscription on file for this user (they may have
     * several — e.g. phone + desktop). A subscription the push service
     * reports as gone (expired, uninstalled) is deleted so it stops being
     * retried on every future reminder.
     */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('services.vapid.subject'),
                    'publicKey' => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ]);
        } catch (Throwable $e) {
            report($e);

            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url ?? '/dashboard',
        ]);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            // 404/410 means the browser has permanently invalidated this
            // endpoint (uninstalled, permission revoked) — stop retrying it.
            if ($report->isSubscriptionExpired()) {
                $user->pushSubscriptions()->where('endpoint', $report->getEndpoint())->delete();
            } else {
                Log::warning('Web push delivery failed', [
                    'user_id' => $user->id,
                    'reason' => $report->getReason(),
                ]);
            }
        }
    }
}
