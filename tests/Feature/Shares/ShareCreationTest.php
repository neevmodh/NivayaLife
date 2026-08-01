<?php

namespace Tests\Feature\Shares;

use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers POST /shares — specifically that every field marked 'nullable' in
 * ShareController::store()'s validation rules is genuinely optional to omit
 * from the request entirely, not just safe to submit as blank/empty. Laravel's
 * validate() only includes keys that were actually present in the request,
 * so reading an absent optional field via $validated['x'] directly (instead
 * of $validated['x'] ?? null) throws "Undefined array key" — this was a
 * real 500 whenever shared_with_label wasn't submitted at all.
 */
class ShareCreationTest extends TestCase
{
    use RefreshDatabase;

    private function actingUserWithReport(): array
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

        $report = Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $user->id,
            'type' => 'blood_test',
            'file_path' => 'reports/x.pdf',
            'original_filename' => 'x.pdf',
            'mime_type' => 'application/pdf',
            'ocr_status' => 'completed',
        ]);

        return [$user, $familyMember, $report];
    }

    public function test_share_can_be_created_without_the_optional_label(): void
    {
        [$user, $familyMember, $report] = $this->actingUserWithReport();

        $response = $this->actingAs($user)->post('/shares', [
            'access_type' => 'single_report',
            'report_id' => $report->id,
            'family_member_id' => $familyMember->id,
            'expiry_option' => '24h',
        ]);

        $response->assertOk();

        $share = Share::where('report_id', $report->id)->first();
        $this->assertNotNull($share);
        $this->assertNull($share->shared_with_label);
    }

    public function test_share_can_be_created_with_a_label(): void
    {
        [$user, $familyMember, $report] = $this->actingUserWithReport();

        $response = $this->actingAs($user)->post('/shares', [
            'access_type' => 'single_report',
            'report_id' => $report->id,
            'family_member_id' => $familyMember->id,
            'expiry_option' => '24h',
            'shared_with_label' => 'Dr. Smith',
        ]);

        $response->assertOk();

        $share = Share::where('report_id', $report->id)->first();
        $this->assertSame('Dr. Smith', $share->shared_with_label);
    }

    public function test_full_summary_share_can_be_created_without_a_report(): void
    {
        [$user, $familyMember] = $this->actingUserWithReport();

        $response = $this->actingAs($user)->post('/shares', [
            'access_type' => 'full_summary',
            'family_member_id' => $familyMember->id,
            'expiry_option' => '7d',
        ]);

        $response->assertOk();

        $share = Share::where('family_member_id', $familyMember->id)->first();
        $this->assertNotNull($share);
        $this->assertNull($share->report_id);
        $this->assertSame('full_summary', $share->access_type);
    }
}
