<?php

namespace Tests\Feature\Api;

use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * These endpoints return medical records, so the interesting question is not
 * whether they work — it is whether one account can reach another's data.
 */
class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_caller_cannot_read_another_accounts_family_member(): void
    {
        $mine = User::factory()->create();
        $mine->ensureLinkedFamilyMember();

        $theirs = User::factory()->create();
        $theirMember = $theirs->ensureLinkedFamilyMember();

        Sanctum::actingAs($mine);

        $this->getJson("/api/dashboard?member={$theirMember->id}")->assertForbidden();
        $this->getJson("/api/reports?member={$theirMember->id}")->assertForbidden();
    }

    public function test_a_caller_cannot_toggle_a_dose_on_another_accounts_medication(): void
    {
        $mine = User::factory()->create();
        $mine->ensureLinkedFamilyMember();

        $theirs = User::factory()->create();
        $theirMember = $theirs->ensureLinkedFamilyMember();

        $medication = Medication::create([
            'family_member_id' => $theirMember->id,
            'medicine_name' => 'Metformin',
            'dosage' => '500 mg',
            'schedule_times' => ['08:00'],
            'active' => true,
        ]);

        Sanctum::actingAs($mine);

        $this->postJson("/api/medications/{$medication->id}/toggle-dose", ['time' => '08:00'])
            ->assertForbidden();
    }

    public function test_a_missing_member_is_a_404_not_a_403(): void
    {
        $user = User::factory()->create();
        $user->ensureLinkedFamilyMember();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard?member=999999')->assertNotFound();
    }

    public function test_the_dashboard_only_lists_the_callers_own_family(): void
    {
        $mine = User::factory()->create();
        $mineMember = $mine->ensureLinkedFamilyMember();

        $theirs = User::factory()->create();
        $theirs->ensureLinkedFamilyMember();

        FamilyMember::create([
            'primary_account_id' => $mine->id,
            'relation' => 'son',
            'full_name' => 'Vivaan Shah',
            'access_type' => 'dependent',
            'status' => 'active',
        ]);

        Sanctum::actingAs($mine);
        $response = $this->getJson('/api/dashboard');

        $response->assertOk();
        $ids = collect($response->json('family_members'))->pluck('id');

        $this->assertTrue($ids->contains($mineMember->id));
        $this->assertCount(2, $ids);
        // Nobody else's records leak into the list.
        $this->assertFalse($ids->contains($theirs->ensureLinkedFamilyMember()->id));
    }

    public function test_report_search_stays_within_the_callers_records(): void
    {
        $mine = User::factory()->create();
        $mineMember = $mine->ensureLinkedFamilyMember();

        $theirs = User::factory()->create();
        $theirMember = $theirs->ensureLinkedFamilyMember();

        Report::create([
            'family_member_id' => $theirMember->id,
            'uploaded_by_user_id' => $theirs->id,
            'type' => 'blood_test',
            'file_path' => 'reports/secret.pdf',
            'original_filename' => 'secret.pdf',
            'ocr_status' => 'completed',
            'ai_summary' => 'A very private finding',
            'uploaded_at' => now(),
        ]);

        Report::create([
            'family_member_id' => $mineMember->id,
            'uploaded_by_user_id' => $mine->id,
            'type' => 'blood_test',
            'file_path' => 'reports/mine.pdf',
            'original_filename' => 'mine.pdf',
            'ocr_status' => 'completed',
            'ai_summary' => 'My own finding',
            'uploaded_at' => now(),
        ]);

        Sanctum::actingAs($mine);
        $response = $this->getJson('/api/reports?q=finding');

        $response->assertOk();
        $response->assertDontSee('A very private finding');
        $response->assertSee('My own finding');
    }
}
