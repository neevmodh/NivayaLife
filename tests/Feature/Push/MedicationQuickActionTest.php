<?php

namespace Tests\Feature\Push;

use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Covers the signed, no-login-required URLs behind a medication push
 * notification's "Mark as taken" / "Snooze" action buttons — the service
 * worker hits these directly, with no session guaranteed on that device.
 */
class MedicationQuickActionTest extends TestCase
{
    use RefreshDatabase;

    private function makeLog(): MedicationLog
    {
        $user = User::factory()->create();
        $familyMember = FamilyMember::create([
            'primary_account_id' => $user->id,
            'linked_user_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'linked',
            'status' => 'active',
        ]);
        $medication = Medication::create([
            'family_member_id' => $familyMember->id,
            'medicine_name' => 'Metformin',
            'schedule_times' => ['08:00'],
            'active' => true,
        ]);

        return MedicationLog::create([
            'medication_id' => $medication->id,
            'scheduled_at' => now(),
            'reminded_at' => now(),
            'status' => 'pending',
        ]);
    }

    public function test_the_taken_action_marks_the_dose_taken_without_authentication(): void
    {
        $log = $this->makeLog();
        $url = URL::temporarySignedRoute('medications.quick-action', now()->addHours(6), ['log' => $log->id, 'action' => 'taken']);

        $this->getJson($url)->assertOk()->assertJson(['status' => 'taken']);

        $log->refresh();
        $this->assertSame('taken', $log->status);
        $this->assertNotNull($log->taken_at);
    }

    public function test_the_snooze_action_pushes_the_scheduled_time_forward_and_clears_reminded_at(): void
    {
        $log = $this->makeLog();
        $url = URL::temporarySignedRoute('medications.quick-action', now()->addHours(6), ['log' => $log->id, 'action' => 'snooze']);

        $this->getJson($url)->assertOk()->assertJson(['status' => 'pending']);

        $log->refresh();
        $this->assertSame('pending', $log->status);
        $this->assertNull($log->reminded_at);
        $this->assertTrue($log->scheduled_at->greaterThan(now()->addMinutes(5)));
    }

    public function test_an_unsigned_url_is_rejected(): void
    {
        $log = $this->makeLog();

        $this->getJson("/medications/dose/{$log->id}/quick-action/taken")->assertForbidden();

        $this->assertSame('pending', $log->fresh()->status);
    }

    public function test_acting_on_a_dose_that_is_no_longer_pending_is_a_no_op(): void
    {
        $log = $this->makeLog();
        $log->update(['status' => 'taken', 'taken_at' => now()->subMinutes(10)]);
        $takenAt = $log->taken_at;

        $url = URL::temporarySignedRoute('medications.quick-action', now()->addHours(6), ['log' => $log->id, 'action' => 'snooze']);
        $this->getJson($url)->assertOk()->assertJson(['status' => 'taken']);

        $this->assertTrue($log->fresh()->taken_at->equalTo($takenAt));
    }
}
