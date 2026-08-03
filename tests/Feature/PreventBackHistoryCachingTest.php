<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the header that stops a browser's back button from restoring a
 * cached authenticated page (dashboard, profile) after logout/account
 * deletion via bfcache, without a fresh request ever reaching the server.
 */
class PreventBackHistoryCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_are_not_cacheable(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_public_pages_are_not_cacheable_either(): void
    {
        $response = $this->get('/login');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
