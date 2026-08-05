<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Every API endpoint reads someone's medical records, so the scoping question
 * — "is this caller allowed to see this member?" — is answered in exactly one
 * place rather than re-implemented per controller.
 */
trait ScopesToCaller
{
    /**
     * Family member ids this user may read: the ones they own, plus the one
     * they are themselves linked to.
     */
    protected function accessibleMemberIds(User $user): Collection
    {
        return FamilyMember::query()
            ->where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->pluck('id');
    }

    /**
     * Resolves the member being asked about, 403ing rather than 404ing when
     * it exists but belongs to someone else — the caller should not be able
     * to probe which ids are real.
     */
    protected function resolveMember(User $user, ?int $requestedId): FamilyMember
    {
        if ($requestedId === null) {
            return $user->ensureLinkedFamilyMember();
        }

        $member = FamilyMember::find($requestedId);

        abort_if($member === null, 404, 'That family member does not exist.');
        abort_unless(
            $member->primary_account_id === $user->id || $member->linked_user_id === $user->id,
            403,
            'You do not have access to that family member.'
        );

        return $member;
    }
}
