<?php

namespace Tests\Feature\Auth;

use App\Models\BmiLog;
use App\Models\Consent;
use App\Models\FamilyMember;
use App\Models\User;
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
     * Registration is a single form (no wizard, nothing session-backed
     * across requests): only full name, email, password, phone, and gender
     * are required. Everything else — date of birth, blood group, photo,
     * address, health info, emergency contact — is left null and filled in
     * later from /profile.
     */
    public function test_new_users_can_register_with_only_the_required_fields(): void
    {
        $response = $this->postJson('/register', [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '9876543210',
            'gender' => 'male',
        ]);

        $this->assertAuthenticated();
        $response->assertJson(['success' => true, 'redirect' => route('dashboard')]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('9876543210', $user->phone);

        $familyMember = FamilyMember::where('primary_account_id', $user->id)->first();
        $this->assertNotNull($familyMember);
        $this->assertSame('self', $familyMember->relation);
        $this->assertSame('male', $familyMember->gender);
        $this->assertNull($familyMember->date_of_birth);
        $this->assertNull($familyMember->blood_group);
        $this->assertNull($familyMember->height_cm);
        $this->assertNull($familyMember->weight_kg);
        $this->assertNull($familyMember->emergency_contact_name);

        $this->assertSame(0, BmiLog::where('family_member_id', $familyMember->id)->count());
        $this->assertSame(0, Consent::where('user_id', $user->id)->count());
    }

    public function test_checked_consent_boxes_are_recorded(): void
    {
        $response = $this->postJson('/register', [
            'full_name' => 'Test User',
            'email' => 'consenting@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '9876543210',
            'gender' => 'female',
            'consent_account_creation' => true,
            'consent_upload' => true,
            // consent_ai_processing intentionally omitted
        ]);

        $response->assertJson(['success' => true]);

        $user = User::where('email', 'consenting@example.com')->first();

        $this->assertSame(
            ['account_creation', 'upload'],
            Consent::where('user_id', $user->id)->pluck('consent_type')->sort()->values()->all()
        );
    }

    /**
     * A real, unmodified HTML checkbox with no explicit value="" submits
     * the literal string "on" when checked — not a PHP/JSON boolean. This
     * previously broke registration for anyone who checked a consent box,
     * since the request was validated with a strict 'boolean' rule that
     * rejects "on". Covers the exact string a browser actually sends.
     */
    public function test_registration_succeeds_with_raw_checkbox_on_values(): void
    {
        $response = $this->post('/register', [
            'full_name' => 'Test User',
            'email' => 'checkbox-on@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '9876543210',
            'gender' => 'male',
            'consent_account_creation' => 'on',
            'consent_upload' => 'on',
            'consent_ai_processing' => 'on',
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $user = User::where('email', 'checkbox-on@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(
            ['account_creation', 'ai_processing', 'upload'],
            Consent::where('user_id', $user->id)->pluck('consent_type')->sort()->values()->all()
        );
    }

    public function test_registration_fails_without_the_required_fields(): void
    {
        $response = $this->postJson('/register', [
            'full_name' => 'Test User',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password', 'phone', 'gender']);
        $this->assertGuest();
    }
}
