<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAiUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_see_the_ai_usage_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/ai-usage')
            ->assertOk()
            ->assertSee('Estimated spend', false)
            ->assertSee('Heaviest assistant users', false);
    }

    public function test_regular_users_are_refused(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/ai-usage')->assertForbidden();
    }
}
