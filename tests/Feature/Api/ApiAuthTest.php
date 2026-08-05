<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_token(): void
    {
        User::factory()->create([
            'email' => 'aarav@example.com',
            'password' => Hash::make('correct-horse'),
            'has_password' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'aarav@example.com',
            'password' => 'correct-horse',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'aarav@example.com',
            'password' => Hash::make('correct-horse'),
            'has_password' => true,
        ]);

        $this->postJson('/api/login', [
            'email' => 'aarav@example.com',
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    /**
     * An unknown email and a wrong password must be indistinguishable, or the
     * endpoint becomes a way to discover which addresses have accounts.
     */
    public function test_unknown_email_and_wrong_password_give_the_same_answer(): void
    {
        User::factory()->create([
            'email' => 'aarav@example.com',
            'password' => Hash::make('correct-horse'),
            'has_password' => true,
        ]);

        $wrongPassword = $this->postJson('/api/login', [
            'email' => 'aarav@example.com',
            'password' => 'wrong',
        ]);

        $unknownEmail = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ]);

        $this->assertSame($wrongPassword->status(), $unknownEmail->status());
        $this->assertSame(
            $wrongPassword->json('errors.email'),
            $unknownEmail->json('errors.email'),
        );
    }

    /** Google-only accounts hold an unusable random password. */
    public function test_a_google_only_account_cannot_sign_in_with_a_password(): void
    {
        User::factory()->create([
            'email' => 'google@example.com',
            'password' => Hash::make('whatever'),
            'has_password' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'google@example.com',
            'password' => 'whatever',
        ])->assertStatus(422);
    }

    public function test_protected_endpoints_reject_anonymous_callers(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/reports')->assertUnauthorized();
        $this->getJson('/api/me')->assertUnauthorized();
        $this->postJson('/api/assistant/send', ['message' => 'hi'])->assertUnauthorized();
    }

    /**
     * Asserted against the token table rather than by re-requesting: the test
     * harness keeps one application instance per test and the auth guard
     * caches the resolved user, so a second call would pass regardless.
     */
    public function test_logout_revokes_only_the_calling_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('other-device');
        $revoke = $user->createToken('this-device');

        $this->assertSame(2, $user->tokens()->count());

        $this->withHeader('Authorization', "Bearer {$revoke->plainTextToken}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertNull($user->tokens()->find($revoke->accessToken->id));
        // The other device must still be signed in.
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('other-device', $user->tokens()->first()->name);
    }
}
