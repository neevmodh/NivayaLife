<?php

namespace Tests\Feature\Api;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The Flutter app parses these responses by key, so the shape is a contract.
 * These tests exist to make a rename here fail loudly rather than silently
 * blanking a screen on someone's phone.
 */
class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_the_shape_the_app_parses(): void
    {
        $user = User::factory()->create();
        $user->ensureLinkedFamilyMember();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'active' => ['id', 'full_name', 'relation', 'photo_url', 'avatar_preset', 'blood_group', 'gender', 'age', 'unique_health_id'],
                'family_members',
                'reports',
                'medications',
                'vaccinations',
                'bmi',
                'bmi_category',
            ]);
    }

    public function test_medications_report_which_of_todays_doses_are_taken(): void
    {
        $user = User::factory()->create();
        $member = $user->ensureLinkedFamilyMember();

        $medication = Medication::create([
            'family_member_id' => $member->id,
            'medicine_name' => 'Metformin',
            'dosage' => '500 mg',
            'schedule_times' => ['08:00', '14:00', '21:00'],
            'active' => true,
        ]);

        MedicationLog::create([
            'medication_id' => $medication->id,
            'scheduled_at' => today()->setTimeFromTimeString('08:00'),
            'status' => 'taken',
            'taken_at' => now(),
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertSame(['08:00', '14:00', '21:00'], $response->json('medications.0.schedule_times'));
        $this->assertSame(['08:00'], $response->json('medications.0.taken_times'));
    }

    public function test_toggling_a_dose_flips_it_and_flips_back(): void
    {
        $user = User::factory()->create();
        $member = $user->ensureLinkedFamilyMember();

        $medication = Medication::create([
            'family_member_id' => $member->id,
            'medicine_name' => 'Metformin',
            'schedule_times' => ['08:00'],
            'active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/medications/{$medication->id}/toggle-dose", ['time' => '08:00'])
            ->assertOk()
            ->assertJson(['status' => 'taken']);

        $this->postJson("/api/medications/{$medication->id}/toggle-dose", ['time' => '08:00'])
            ->assertOk()
            ->assertJson(['status' => 'pending']);
    }

    /**
     * The guardrail must hold over the API exactly as it does on the web —
     * a safety rule that only applies on one platform is not a safety rule.
     */
    public function test_the_assistant_answers_an_emergency_without_calling_the_model(): void
    {
        $user = User::factory()->create();
        $user->ensureLinkedFamilyMember();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/assistant/send', [
            'message' => 'I have really bad chest pain right now',
        ]);

        $response->assertOk();
        $response->assertJson(['urgent' => true]);
        $this->assertStringContainsString('emergency', strtolower($response->json('reply')));
    }

    public function test_the_assistant_rejects_an_over_long_message(): void
    {
        $user = User::factory()->create();
        $user->ensureLinkedFamilyMember();

        Sanctum::actingAs($user);

        $this->postJson('/api/assistant/send', ['message' => str_repeat('a', 1001)])
            ->assertStatus(422);
    }
}
