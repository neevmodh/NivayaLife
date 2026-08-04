<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\FamilyInvitation;
use App\Models\FamilyMember;
use App\Models\SharingPermission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $invitation = FamilyInvitation::where('token', $token)->first();

        if (! $invitation) {
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }

            abort(404);
        }

        if ($invitation->status === 'pending' && $invitation->expires_at->isPast()) {
            $invitation->update(['status' => 'expired']);
        }

        if ($invitation->status === 'expired') {
            return view('invite.expired', ['invitation' => $invitation]);
        }

        if ($invitation->status === 'accepted') {
            if (Auth::check() && $this->belongsToCurrentUser($invitation, Auth::user())) {
                return $this->permissionScreenOrDashboard($invitation);
            }

            return view('invite.already-accepted', ['invitation' => $invitation]);
        }

        // status is 'pending' and not expired from here on.
        if (Auth::check()) {
            if (strcasecmp(Auth::user()->email, $invitation->invited_email) !== 0) {
                return view('invite.wrong-account', ['invitation' => $invitation]);
            }

            return $this->acceptAndShowPermissionScreen($invitation);
        }

        $existingUser = User::where('email', $invitation->invited_email)->first();

        if ($existingUser) {
            $request->session()->put('url.intended', route('invite.show', $token));

            return view('invite.login-prompt', ['invitation' => $invitation]);
        }

        return view('invite.signup', ['invitation' => $invitation]);
    }

    public function register(Request $request, string $token): JsonResponse
    {
        $invitation = FamilyInvitation::where('token', $token)->where('status', 'pending')->first();
        abort_if(! $invitation || $invitation->expires_at->isPast(), 404);
        abort_if(User::where('email', $invitation->invited_email)->exists(), 422);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
            'phone' => ['required', 'digits:10'],
            'country_code' => ['required', 'regex:/^\+[1-9]\d{0,3}$/'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $photoPath = 'avatars/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($photoPath, file_get_contents($request->file('photo')->getRealPath()));

        $user = DB::transaction(function () use ($invitation, $validated, $photoPath, $request) {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $invitation->invited_email,
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'phone_country_code' => $validated['country_code'],
                'avatar_path' => $photoPath,
                'email_verified_at' => now(),
            ]);

            // A brand-new signup can never already own a self-record, so
            // linking straight onto this invitation's shell row is always
            // safe here — no risk of the linked_user_id unique conflict.
            $familyMember = $invitation->familyMember;
            $familyMember->update([
                'linked_user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'photo_path' => $photoPath,
                'status' => 'active',
            ]);

            $invitation->update(['status' => 'accepted']);

            Consent::grant($user, 'account_creation', $request);

            AuditLog::record('family_invitation_accepted', 'FamilyMember', $familyMember->id, $user->id, $familyMember->id);

            return $user;
        });

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return response()->json(['success' => true, 'redirect' => route('invite.show', $token)]);
    }

    /**
     * "No, not now" on the in-app invitation popup. family_invitations.status
     * only has pending/accepted/expired (no "declined") — a real decline
     * would need a schema change, so this just quiets the popup for the rest
     * of the current session. It resurfaces on their next fresh login, and
     * the invitation itself is untouched (still fully acceptable via the link).
     */
    public function dismiss(Request $request, string $token): RedirectResponse
    {
        $dismissed = $request->session()->get('dismissed_invitations', []);
        $dismissed[] = $token;
        $request->session()->put('dismissed_invitations', array_unique($dismissed));

        return back();
    }

    public function storePermission(Request $request, string $token): RedirectResponse
    {
        $invitation = FamilyInvitation::where('token', $token)->where('status', 'accepted')->firstOrFail();
        abort_unless($this->belongsToCurrentUser($invitation, Auth::user()), 403);

        $validated = $request->validate([
            'scope' => ['required', 'in:full,reports_only,summary_only'],
        ]);

        $grantTarget = $this->grantTargetFor($invitation, Auth::user());

        // sharing_permissions' unique constraint covers every row for this
        // (family_member, grantee) pair regardless of revoked_at — a prior
        // grant that was later revoked still occupies that slot, so
        // re-granting must update it in place rather than insert a new row.
        SharingPermission::updateOrCreate(
            ['family_member_id' => $grantTarget->id, 'granted_to_user_id' => $invitation->primary_account_id],
            ['scope' => $validated['scope'], 'granted_at' => now(), 'revoked_at' => null]
        );

        AuditLog::record('sharing_permission_granted', 'SharingPermission', $grantTarget->id, Auth::id(), $grantTarget->id);

        $this->cleanUpShellIfRedundant($invitation, $grantTarget);

        return redirect()->route('dashboard');
    }

    /**
     * family_members.linked_user_id is unique system-wide — every user
     * already owns exactly one "self" row (created at registration), so a
     * *different* invitation's shell row can never point linked_user_id at
     * them too. When the invitee already has their own account, the shell
     * row stays untouched (nothing to link) until the sharing_permission
     * against their *real* self-record is confirmed in storePermission() —
     * only then is the now-redundant shell cleaned up.
     */
    private function acceptAndShowPermissionScreen(FamilyInvitation $invitation): View|RedirectResponse
    {
        $user = Auth::user();
        $isReciprocal = $this->isReciprocalCase($invitation, $user);

        DB::transaction(function () use ($invitation, $user, $isReciprocal) {
            if (! $isReciprocal) {
                $invitation->familyMember->update(['linked_user_id' => $user->id, 'status' => 'active']);
            }

            $invitation->update(['status' => 'accepted']);
            AuditLog::record('family_invitation_accepted', 'FamilyMember', $invitation->family_member_id, $user->id, $invitation->family_member_id);
        });

        return $this->permissionScreenOrDashboard($invitation);
    }

    private function permissionScreenOrDashboard(FamilyInvitation $invitation): View|RedirectResponse
    {
        $grantTarget = $this->grantTargetFor($invitation, Auth::user());

        $existingGrant = SharingPermission::where('family_member_id', $grantTarget->id)
            ->where('granted_to_user_id', $invitation->primary_account_id)
            ->whereNull('revoked_at')
            ->exists();

        if ($existingGrant) {
            $this->cleanUpShellIfRedundant($invitation, $grantTarget);

            return redirect()->route('dashboard');
        }

        return view('invite.permission', ['invitation' => $invitation]);
    }

    /** Whether the invited person, once authenticated, already had their own account before this invite. */
    private function isReciprocalCase(FamilyInvitation $invitation, User $user): bool
    {
        $existingSelf = $user->linkedFamilyMember;

        return $existingSelf !== null && $existingSelf->id !== $invitation->family_member_id;
    }

    /** The family_members row a sharing_permission grant should actually be created against. */
    private function grantTargetFor(FamilyInvitation $invitation, User $user): FamilyMember
    {
        return $this->isReciprocalCase($invitation, $user)
            ? $user->linkedFamilyMember
            : $invitation->familyMember;
    }

    /** True once the invited user is genuinely connected to this invitation, however it resolved. */
    private function belongsToCurrentUser(FamilyInvitation $invitation, User $user): bool
    {
        if ($invitation->familyMember?->linked_user_id === $user->id) {
            return true;
        }

        // Reciprocal case: the shell row is deliberately left untouched
        // (never linked) until storePermission() finishes, so its mere
        // existence — for this invitation, matching this user's email — is
        // itself the signal they're still mid-flow and should resume there.
        return $this->isReciprocalCase($invitation, $user)
            && $invitation->familyMember !== null
            && strcasecmp($user->email, $invitation->invited_email) === 0;
    }

    /**
     * The shell row this invitation created never became anyone's real
     * profile in the reciprocal case — safe to hard-delete once the grant
     * it existed to enable has been made. Cascades the invitation row away
     * too; audit_log already holds the permanent record of what happened.
     */
    private function cleanUpShellIfRedundant(FamilyInvitation $invitation, FamilyMember $grantTarget): void
    {
        $shell = $invitation->familyMember;

        if ($shell && $shell->id !== $grantTarget->id) {
            $shell->forceDelete();
        }
    }
}
