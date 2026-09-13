<?php

namespace Tests\Feature\Reports;

use App\Models\FamilyMember;
use App\Models\User;
use App\Rules\ReadableDocumentImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A photo too small for OCR fails silently and dangerously rather than
 * obviously: Tesseract drops decimal points, so "7.8" becomes "78" and the
 * summary reports an impossible HbA1c as fact. Measured through the full
 * pipeline, dense prose stopped extracting at all below ~533px wide, so
 * uploads are refused under 600px on the shorter side.
 */
class UploadDimensionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingMember(): array
    {
        $user = User::factory()->create();
        $member = FamilyMember::create([
            'primary_account_id' => $user->id,
            'linked_user_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'linked',
            'status' => 'active',
        ]);

        return [$user, $member];
    }

    public function test_an_image_below_the_readable_floor_is_refused(): void
    {
        Storage::fake('local');
        Queue::fake();
        [$user, $member] = $this->actingMember();

        $response = $this->actingAs($user)->postJson(route('reports.store'), [
            'family_member_id' => $member->id,
            'file' => UploadedFile::fake()->image('tiny.jpg', 410, 500),
            'type' => 'blood_test',
            'report_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('file');
        $this->assertStringContainsString('too small to read reliably', $response->json('errors.file.0'));
    }

    public function test_an_adequately_sized_image_is_accepted(): void
    {
        Storage::fake('local');
        Queue::fake();
        [$user, $member] = $this->actingMember();

        $this->actingAs($user)->postJson(route('reports.store'), [
            'family_member_id' => $member->id,
            'file' => UploadedFile::fake()->image('good.jpg', 820, 1000),
            'type' => 'blood_test',
            'report_date' => now()->toDateString(),
        ])->assertOk();
    }

    /** Orientation must not matter — a landscape scan is judged on its shorter side too. */
    public function test_the_shorter_side_is_what_counts(): void
    {
        $this->assertTrue($this->failsRule(2000, 400));
        $this->assertTrue($this->failsRule(400, 2000));
        $this->assertFalse($this->failsRule(700, 900));
    }

    /** PDFs are rasterised at 300 DPI downstream, so pixel dimensions do not apply. */
    public function test_a_pdf_is_exempt(): void
    {
        $failed = false;
        (new ReadableDocumentImage)->validate(
            'file',
            UploadedFile::fake()->create('scan.pdf', 200, 'application/pdf'),
            function () use (&$failed) { $failed = true; }
        );

        $this->assertFalse($failed);
    }

    private function failsRule(int $width, int $height): bool
    {
        $failed = false;
        (new ReadableDocumentImage)->validate(
            'file',
            UploadedFile::fake()->image('x.jpg', $width, $height),
            function () use (&$failed) { $failed = true; }
        );

        return $failed;
    }
}
