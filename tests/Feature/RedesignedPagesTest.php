<?php

namespace Tests\Feature;

use App\Models\Allergy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedesignedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_renders(): void
    {
        $user = User::factory()->create();
        $user->ensureLinkedFamilyMember();

        $this->actingAs($user)->get('/timeline')->assertOk();
    }

    public function test_family_page_renders(): void
    {
        $user = User::factory()->create();
        $user->ensureLinkedFamilyMember();

        $this->actingAs($user)->get('/family')->assertOk();
    }

    /**
     * The emergency card is the page a stranger reads, so the facts that
     * matter in a hurry must actually be on it.
     */
    public function test_emergency_card_leads_with_the_critical_facts(): void
    {
        $user = User::factory()->create();
        $member = $user->ensureLinkedFamilyMember();
        $member->update([
            'blood_group' => 'O+',
            'emergency_contact_name' => 'Meera Shah',
            'emergency_contact_phone' => '9876543210',
        ]);

        Allergy::create([
            'family_member_id' => $member->id,
            'allergen_name' => 'Penicillin',
            'severity' => 'severe',
        ]);

        $card = $member->idCard ?? \App\Models\IdCard::generateCard($member);

        $response = $this->get("/emergency/{$card->card_number}");

        $response->assertOk();
        $response->assertSee('O+', false);
        $response->assertSee('Penicillin', false);
        $response->assertSee('tel:9876543210', false);
    }
}
