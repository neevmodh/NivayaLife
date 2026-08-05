<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_search_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);
        User::factory()->create(['name' => 'Findable Person', 'email' => 'findable@example.com']);
        User::factory()->create(['name' => 'Other Person', 'email' => 'other@example.com']);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Findable Person', false);

        $this->actingAs($admin)->get('/admin/users?q=findable')
            ->assertOk()
            ->assertSee('findable@example.com', false)
            // The non-matching account must be filtered out of the results.
            ->assertDontSee('other@example.com', false);
    }

    public function test_admin_can_open_a_user_detail_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['name' => 'Target Person']);

        $this->actingAs($admin)->get("/admin/users/{$target->id}")
            ->assertOk()
            ->assertSee('Target Person', false)
            ->assertSee('Assistant messages', false);
    }

    public function test_failed_jobs_page_renders_when_empty(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/failed-jobs')
            ->assertOk()
            ->assertSee('Nothing has failed', false);
    }

    public function test_retrying_a_job_that_is_gone_reports_it_rather_than_erroring(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/failed-jobs/does-not-exist/retry')
            ->assertRedirect();
    }

    public function test_regular_users_cannot_reach_any_of_it(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/failed-jobs')->assertForbidden();
    }
}
