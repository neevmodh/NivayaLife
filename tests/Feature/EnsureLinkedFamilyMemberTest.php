<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureLinkedFamilyMemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reading the relation caches a null, so the old `?? create()` version
     * inserted a second row on the next call and tripped the unique index.
     */
    public function test_calling_it_twice_on_the_same_instance_is_safe(): void
    {
        $user = User::factory()->create();

        $first = $user->ensureLinkedFamilyMember();
        $second = $user->ensureLinkedFamilyMember();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, FamilyMember::where('linked_user_id', $user->id)->count());
    }

    public function test_it_returns_the_existing_record_rather_than_a_new_one(): void
    {
        $user = User::factory()->create();
        $existing = $user->ensureLinkedFamilyMember();

        // A freshly loaded instance must find the same record.
        $reloaded = User::findOrFail($user->id);

        $this->assertSame($existing->id, $reloaded->ensureLinkedFamilyMember()->id);
        $this->assertSame(1, FamilyMember::where('linked_user_id', $user->id)->count());
    }
}
