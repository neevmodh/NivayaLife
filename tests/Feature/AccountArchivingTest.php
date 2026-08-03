<?php

namespace Tests\Feature;

use App\Models\ArchivedAccount;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Account deletion isn't a hard, no-trace delete: a full snapshot is
 * written to archived_accounts first (same transaction), then the live
 * rows are removed exactly as before. A later signup with the same email
 * must be a completely fresh account, unrelated to the archive.
 */
class AccountArchivingTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_account_archives_its_data_before_removing_it(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email' => 'archived@example.com']);
        $familyMember = FamilyMember::create([
            'primary_account_id' => $user->id,
            'linked_user_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'linked',
            'status' => 'active',
        ]);
        Medication::create([
            'family_member_id' => $familyMember->id,
            'medicine_name' => 'Metformin',
            'schedule_times' => ['08:00'],
            'active' => true,
        ]);

        $path = 'reports/'.uniqid('rx_').'.pdf';
        Storage::disk('local')->put($path, 'fake pdf bytes');
        $report = Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $user->id,
            'type' => 'prescription',
            'file_path' => $path,
            'original_filename' => 'rx.pdf',
            'mime_type' => 'application/pdf',
            'ocr_status' => 'completed',
        ]);

        $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
            'confirmation' => 'DELETE',
        ])->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertNull(FamilyMember::withTrashed()->find($familyMember->id));
        $this->assertNull(Report::withTrashed()->find($report->id));

        $archived = ArchivedAccount::where('email', 'archived@example.com')->first();
        $this->assertNotNull($archived);
        $this->assertSame($user->id, $archived->original_user_id);
        $this->assertCount(1, $archived->data);
        $this->assertSame('Metformin', $archived->data[0]['medications'][0]['medicine_name']);
        $this->assertSame('rx.pdf', $archived->data[0]['reports'][0]['original_filename']);

        $archivedPath = $archived->archived_files[0]['archived_path'];
        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($archivedPath);
    }

    public function test_reregistering_with_the_same_email_does_not_restore_archived_data(): void
    {
        $original = User::factory()->create(['email' => 'reused@example.com']);
        FamilyMember::create([
            'primary_account_id' => $original->id,
            'linked_user_id' => $original->id,
            'relation' => 'self',
            'full_name' => 'Original Person',
            'access_type' => 'linked',
            'status' => 'active',
        ]);

        $this->actingAs($original)->delete('/profile', [
            'password' => 'password',
            'confirmation' => 'DELETE',
        ]);

        $this->assertNotNull(ArchivedAccount::where('email', 'reused@example.com')->first());

        $response = $this->postJson('/register', [
            'full_name' => 'Brand New Person',
            'email' => 'reused@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'phone' => '9876543210',
            'gender' => 'female',
            'height_cm' => 165,
            'weight_kg' => 60,
        ]);

        $response->assertJson(['success' => true]);

        $newUser = User::where('email', 'reused@example.com')->first();
        $this->assertNotSame($original->id, $newUser->id);

        $newFamilyMember = FamilyMember::where('primary_account_id', $newUser->id)->first();
        $this->assertSame('Brand New Person', $newFamilyMember->full_name);
        $this->assertNotSame('Original Person', $newFamilyMember->full_name);
    }
}
