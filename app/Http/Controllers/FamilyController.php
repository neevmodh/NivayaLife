<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FamilyMember;
use App\Models\SharingPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $self = $user->ensureLinkedFamilyMember();

        $familyMembers = FamilyMember::where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->orWhereHas('sharingPermissions', fn ($q) => $q->where('granted_to_user_id', $user->id)->whereNull('revoked_at'))
            ->with(['invitations' => fn ($q) => $q->latest('id'), 'sharingPermissions' => fn ($q) => $q->whereNull('revoked_at')])
            ->withCount(['reports', 'medications', 'vaccinations'])
            ->orderBy('full_name')
            ->get();

        $active = $familyMembers->filter(fn ($m) => $m->status === 'active')->values();

        $invited = $familyMembers->filter(
            fn ($m) => $m->status === 'invited' && $m->invitations->first()?->status === 'pending'
        )->values();

        $expired = $familyMembers->filter(
            fn ($m) => $m->status === 'invited' && $m->invitations->first()?->status === 'expired'
        )->values();

        // The other half of every reciprocal grant: people this account has
        // shared *its own* profile with. Without this, granting access is a
        // one-way street — only visible to the person who received it, never
        // to the person who gave it, and it could never be reviewed or revoked.
        $sharedWith = SharingPermission::where('family_member_id', $self->id)
            ->whereNull('revoked_at')
            ->with('grantedToUser')
            ->latest()
            ->get();

        return view('family.index', [
            'active' => $active,
            'invited' => $invited,
            'expired' => $expired,
            'sharedWith' => $sharedWith,
            'primaryAccountName' => $user->name,
        ]);
    }

    public function revokeSharing(Request $request, SharingPermission $sharingPermission): RedirectResponse
    {
        $user = $request->user();
        $self = $user->linkedFamilyMember;

        abort_unless($self && $sharingPermission->family_member_id === $self->id, 403);

        $sharingPermission->revoke();

        AuditLog::record('sharing_permission_revoked', 'SharingPermission', $sharingPermission->id, $user->id, $self->id);

        return back()->with('status', 'sharing-revoked');
    }

    public function show(Request $request, FamilyMember $familyMember): View
    {
        $user = $request->user();
        $this->authorizeView($user, $familyMember);

        $canViewFull = $familyMember->hasGrantedAccessTo($user);
        $canEdit = $familyMember->canBeEditedBy($user);

        $latestBmi = $canViewFull ? $familyMember->bmiLogs()->latest('recorded_date')->latest('id')->first() : null;
        $allergies = $canViewFull ? $familyMember->allergies()->pluck('allergen_name') : collect();
        $medications = $canViewFull ? $familyMember->medications()->where('active', true)->get() : collect();

        return view('family.show', [
            'member' => $familyMember,
            'canViewFull' => $canViewFull,
            'canEdit' => $canEdit,
            'latestBmi' => $latestBmi,
            'allergies' => $allergies,
            'medications' => $medications,
        ]);
    }

    public function archive(Request $request, FamilyMember $familyMember): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeManage($user, $familyMember);

        abort_if(
            $familyMember->relation === 'self' && $familyMember->linked_user_id === $user->id,
            403,
            "You can't archive your own profile."
        );

        $familyMember->delete();

        AuditLog::record('family_member_archived', 'FamilyMember', $familyMember->id, $user->id, $familyMember->id);

        return redirect()->route('family.index')->with('status', 'family-member-archived');
    }

    public function restore(Request $request, int $familyMember): RedirectResponse
    {
        $user = $request->user();
        $member = FamilyMember::withTrashed()->findOrFail($familyMember);
        $this->authorizeManage($user, $member);

        $member->restore();

        AuditLog::record('family_member_restored', 'FamilyMember', $member->id, $user->id, $member->id);

        return back()->with('status', 'family-member-restored');
    }

    /** Ownership (managing the record) OR a reciprocal sharing_permission grant (merely viewing it). */
    private function authorizeView($user, FamilyMember $familyMember): void
    {
        $owns = $familyMember->primary_account_id === $user->id || $familyMember->linked_user_id === $user->id;

        $viaGrant = $familyMember->sharingPermissions()
            ->where('granted_to_user_id', $user->id)
            ->whereNull('revoked_at')
            ->exists();

        abort_unless($owns || $viaGrant, 403);
    }

    /**
     * Archiving/restoring is a management action — a reciprocal grant only
     * ever confers *viewing* rights on someone else's own record, never the
     * ability to manage it.
     */
    private function authorizeManage($user, FamilyMember $familyMember): void
    {
        abort_unless(
            $familyMember->primary_account_id === $user->id || $familyMember->linked_user_id === $user->id,
            403
        );
    }
}
