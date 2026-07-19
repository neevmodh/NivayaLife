<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    /**
     * Name/DOB/gender/blood-group live on the profile's "basic info" tab,
     * a JSON endpoint of its own — there's no single combined PATCH /profile
     * covering the whole form, and no email field at all (email changes
     * aren't a feature this app exposes, since it doubles as the login).
     */
    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/profile/basic-info', [
                'full_name' => 'Test User',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'blood_group' => 'O+',
            ]);

        $response->assertJson(['success' => true]);

        $this->assertSame('Test User', $user->fresh()->name);
    }

    /** Full account deletion requires the password plus typing DELETE as an explicit, harder-to-fat-finger confirmation. */
    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
                'confirmation' => 'DELETE',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
                'confirmation' => 'DELETE',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
