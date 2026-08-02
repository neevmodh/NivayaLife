<?php

namespace Tests\Feature\Push;

use App\Console\Commands\ProcessMedicationReminders;
use App\Console\Commands\SendVaccinationReminders;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\User;
use App\Models\Vaccination;
use App\Services\Push\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Covers that both existing email-reminder commands now also attempt a push
 * send per recipient — the email path itself (already covered by the
 * commands' own established behavior) is left untouched; Mail::fake() here
 * only keeps the test from hitting a real mailer.
 */
class ReminderPushTest extends TestCase
{
    use RefreshDatabase;

    private function makeFamilyMember(): FamilyMember
    {
        $user = User::factory()->create();

        return FamilyMember::create([
            'primary_account_id' => $user->id,
            'linked_user_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'linked',
            'status' => 'active',
        ]);
    }

    public function test_medication_reminders_send_a_push_notification_alongside_the_email(): void
    {
        Mail::fake();

        $familyMember = $this->makeFamilyMember();
        $medication = Medication::create([
            'family_member_id' => $familyMember->id,
            'medicine_name' => 'Metformin',
            'dosage' => '500mg',
            'schedule_times' => ['08:00'],
            'active' => true,
            'reminder_enabled' => true,
        ]);
        MedicationLog::create([
            'medication_id' => $medication->id,
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        $mock = Mockery::mock(WebPushService::class);
        $mock->shouldReceive('sendToUser')
            ->once()
            ->withArgs(fn ($user, $title, $body, $url) => $title === 'Medication reminder' && str_contains($body, 'Metformin') && $url === '/medications');
        $this->app->instance(WebPushService::class, $mock);

        $this->artisan(ProcessMedicationReminders::class)->assertSuccessful();
    }

    public function test_vaccination_reminders_send_a_push_notification_alongside_the_email(): void
    {
        Mail::fake();

        $familyMember = $this->makeFamilyMember();
        Vaccination::create([
            'family_member_id' => $familyMember->id,
            'vaccine_name' => 'Tetanus booster',
            'dose_number' => 1,
            'date_administered' => now()->subYears(9),
            'next_due_date' => now()->addDays(3),
        ]);

        $mock = Mockery::mock(WebPushService::class);
        $mock->shouldReceive('sendToUser')
            ->once()
            ->withArgs(fn ($user, $title, $body, $url) => $title === 'Vaccination due soon' && str_contains($body, 'Tetanus booster') && $url === '/vaccinations');
        $this->app->instance(WebPushService::class, $mock);

        $this->artisan(SendVaccinationReminders::class)->assertSuccessful();
    }
}
