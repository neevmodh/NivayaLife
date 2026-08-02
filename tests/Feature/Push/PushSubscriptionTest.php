<?php

namespace Tests\Feature\Push;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_subscription_is_stored_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://fcm.example.com/send/abc123',
            'keys' => ['p256dh' => 'public-key-value', 'auth' => 'auth-token-value'],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.example.com/send/abc123',
        ]);
    }

    public function test_resubscribing_the_same_endpoint_updates_rather_than_duplicates(): void
    {
        $user = User::factory()->create();
        $payload = [
            'endpoint' => 'https://fcm.example.com/send/abc123',
            'keys' => ['p256dh' => 'public-key-value', 'auth' => 'auth-token-value'],
        ];

        $this->actingAs($user)->postJson(route('push-subscriptions.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
            ...$payload,
            'keys' => ['p256dh' => 'rotated-key', 'auth' => 'auth-token-value'],
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['public_key' => 'rotated-key']);
    }

    public function test_a_subscription_can_be_removed(): void
    {
        $user = User::factory()->create();
        $endpoint = 'https://fcm.example.com/send/abc123';

        $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'public-key-value', 'auth' => 'auth-token-value'],
        ])->assertOk();

        $this->actingAs($user)->deleteJson(route('push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_guests_cannot_subscribe(): void
    {
        $this->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://fcm.example.com/send/abc123',
            'keys' => ['p256dh' => 'public-key-value', 'auth' => 'auth-token-value'],
        ])->assertUnauthorized();
    }

    public function test_test_notification_requires_an_existing_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('push-subscriptions.test'))
            ->assertStatus(422)
            ->assertJson(['status' => 'no_subscription']);
    }

    public function test_test_notification_is_sent_once_subscribed(): void
    {
        // Force WebPushService::isConfigured() to false regardless of the
        // environment's real VAPID keys — this test only confirms the
        // endpoint itself responds successfully once subscribed, not that
        // an actual push goes out (that's a job for a real device).
        config(['services.vapid.public_key' => null, 'services.vapid.private_key' => null]);

        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
            'endpoint' => 'https://fcm.example.com/send/abc123',
            'keys' => ['p256dh' => 'public-key-value', 'auth' => 'auth-token-value'],
        ])->assertOk();

        $this->actingAs($user)->postJson(route('push-subscriptions.test'))
            ->assertOk()
            ->assertJson(['status' => 'sent']);
    }
}
