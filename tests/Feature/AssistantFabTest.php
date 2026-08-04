<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantFabTest extends TestCase
{
    use RefreshDatabase;

    public function test_floating_assistant_button_appears_on_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Ask the AI assistant', false)
            ->assertSee('Open assistant', false);
    }

    public function test_it_is_hidden_on_the_assistant_page_itself(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/assistant')
            ->assertOk()
            ->assertDontSee('Ask the AI assistant', false);
    }
}
