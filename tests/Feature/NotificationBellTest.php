<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use App\Models\Vaccination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_clear_account_shows_the_caught_up_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee("You're all caught up");
    }

    public function test_an_overdue_vaccination_appears_in_the_bell(): void
    {
        $user = User::factory()->create();
        // The self record is created lazily on first dashboard visit.
        $member = $user->ensureLinkedFamilyMember();

        Vaccination::create([
            'family_member_id' => $member->id,
            'vaccine_name' => 'Tetanus booster',
            'dose_number' => 1,
            'date_administered' => now()->subYears(5),
            'next_due_date' => now()->subDays(3),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Tetanus booster is overdue', false);
        $response->assertDontSee("You're all caught up");
    }
}
