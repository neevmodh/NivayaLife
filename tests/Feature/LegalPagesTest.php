<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_renders(): void
    {
        $response = $this->get('/terms');

        $response->assertOk();
        $response->assertSee('Terms of Service');
        $response->assertSee('Not medical advice');
    }

    public function test_privacy_page_renders(): void
    {
        $response = $this->get('/privacy');

        $response->assertOk();
        $response->assertSee('Privacy Policy');
        $response->assertSee('Google Gemini');
    }
}
