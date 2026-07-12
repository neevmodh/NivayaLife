<?php

namespace App\Policies;

use App\Models\FamilyMember;
use App\Models\User;

class FamilyMemberPolicy
{
    public function view(User $user, FamilyMember $familyMember): bool
    {
        return $this->owns($user, $familyMember);
    }

    public function update(User $user, FamilyMember $familyMember): bool
    {
        return $this->owns($user, $familyMember);
    }

    public function delete(User $user, FamilyMember $familyMember): bool
    {
        return $familyMember->primary_account_id === $user->id;
    }

    /**
     * Only the primary account owner, or the linked user themselves, may act on this record.
     */
    private function owns(User $user, FamilyMember $familyMember): bool
    {
        return $familyMember->primary_account_id === $user->id
            || ($familyMember->isLinked() && $familyMember->linked_user_id === $user->id);
    }
}
