<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * Registration is a 5-step wizard (session-backed, nothing written to
     * the database until the final step) rather than a single POST /register
     * with name/email/password — this walks the same steps the real wizard
     * UI posts, skipping photo and address since both are optional.
     */
    public function test_new_users_can_register(): void
    {
        $this->postJson('/register/step-1', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'blood_group' => 'O+',
        ])->assertJson(['success' => true, 'next_step' => 2]);

        // Photo (step 2) and address (step 3) are both optional — an empty
        // submission is the same as clicking "Skip for now" in the UI.
        $this->postJson('/register/step-2', [])->assertJson(['success' => true, 'next_step' => 3]);
        $this->postJson('/register/step-3', [])->assertJson(['success' => true, 'next_step' => 4]);

        $this->postJson('/register/step-4', [
            'height_cm' => 170,
            'weight_kg' => 65,
        ])->assertJson(['success' => true, 'next_step' => 5]);

        $response = $this->postJson('/register', [
            'emergency_contact_name' => 'Emergency Contact',
            'emergency_contact_phone' => '9998887777',
            'emergency_contact_relation' => 'Parent',
            'consent_account_creation' => true,
            'consent_upload' => true,
            'consent_ai_processing' => true,
        ]);

        $this->assertAuthenticated();
        $response->assertJson(['success' => true, 'redirect' => route('dashboard')]);
    }
}
