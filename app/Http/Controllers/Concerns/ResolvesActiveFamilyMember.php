<?php

namespace App\Http\Controllers\Concerns;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Same "which family member is this page about" resolution used by the
 * dashboard and ID card pages: an explicit ?member= query param wins, then
 * the session's active_family_member_id, then the user's own self-record.
 */
trait ResolvesActiveFamilyMember
{
    protected function resolveActiveFamilyMember(Request $request, User $user): FamilyMember
    {
        $user->ensureLinkedFamilyMember();

        $familyMembers = FamilyMember::where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->orWhereHas('sharingPermissions', fn ($q) => $q->where('granted_to_user_id', $user->id)->whereNull('revoked_at'))
            ->orderByRaw("relation = 'self' desc")
            ->orderBy('full_name')
            ->get();

        $requestedId = $request->integer('member') ?: session('active_family_member_id');

        return $familyMembers->firstWhere('id', $requestedId)
            ?? $familyMembers->firstWhere('relation', 'self')
            ?? $familyMembers->first();
    }
}
