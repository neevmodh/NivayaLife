<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_assistant_page_renders_dictation_controls(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/assistant');

        $response->assertOk();
        $response->assertSee('Dictate your question', false);
        $response->assertSee('Speak in', false);
        $response->assertSee('assistantChat(', false);
    }
}
