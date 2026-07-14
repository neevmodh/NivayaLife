<?php

namespace App\Http\Controllers;

use App\Mail\FamilyInvitationMail;
use App\Models\AuditLog;
use App\Models\FamilyInvitation;
use App\Models\FamilyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FamilyInviteController extends Controller
{
    /**
     * Creates the family_members "shell" row (placeholder name, no login yet)
     * plus the family_invitations row, and emails the invite. Nothing about
     * the invitee's actual identity is known until they accept.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'relation' => ['required', 'in:spouse,father,mother,son,daughter,grandfather,grandmother,other'],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $alreadyPending = FamilyInvitation::where('primary_account_id', $user->id)
            ->where('invited_email', $validated['email'])
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'success' => false,
                'errors' => ['email' => ['You already have a pending invitation for this email.']],
            ], 422);
        }

        $alreadyLinked = FamilyMember::where('primary_account_id', $user->id)
            ->whereHas('linkedUser', fn ($q) => $q->where('email', $validated['email']))
            ->exists();

        if ($alreadyLinked) {
            return response()->json([
                'success' => false,
                'errors' => ['email' => ['This email is already linked to a family member on your account.']],
            ], 422);
        }

        $invitation = DB::transaction(function () use ($user, $validated) {
            $familyMember = FamilyMember::create([
                'primary_account_id' => $user->id,
                'linked_user_id' => null,
                'relation' => $validated['relation'],
                'full_name' => $validated['full_name'],
                'access_type' => 'linked',
                'status' => 'invited',
            ]);

            $invitation = FamilyInvitation::create([
                'primary_account_id' => $user->id,
                'family_member_id' => $familyMember->id,
                'invited_email' => $validated['email'],
                'invited_by' => $user->id,
                'status' => 'pending',
            ]);

            AuditLog::record('family_invitation_sent', 'FamilyInvitation', $invitation->id, $user->id, $familyMember->id);

            return $invitation;
        });

        Mail::to($invitation->invited_email)->send(new FamilyInvitationMail($invitation));

        $inviteUrl = route('invite.show', $invitation->token);
        $qrSvg = QrCode::size(180)->margin(0)->generate($inviteUrl);

        return response()->json([
            'success' => true,
            'invite_url' => $inviteUrl,
            'name' => $invitation->familyMember->full_name,
            'qr_data_uri' => 'data:image/svg+xml;base64,'.base64_encode($qrSvg),
        ]);
    }

    public function resend(Request $request, FamilyInvitation $invitation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($invitation->primary_account_id === $user->id, 403);

        if ($invitation->status !== 'pending') {
            return back()->withErrors(['resend' => 'This invitation is no longer pending.']);
        }

        if ($invitation->updated_at->gt(now()->subMinutes(2))) {
            return back()->withErrors(['resend' => 'Please wait a couple of minutes before resending.']);
        }

        $invitation->update(['expires_at' => now()->addDays(7)]);

        Mail::to($invitation->invited_email)->send(new FamilyInvitationMail($invitation));

        AuditLog::record('family_invitation_resent', 'FamilyInvitation', $invitation->id, $user->id, $invitation->family_member_id);

        return back()->with('status', 'invite-resent');
    }

    /**
     * A cancelled invite never had any real person or health data attached
     * to its placeholder family_members row, so — unlike archiving an active
     * member — a genuine hard delete is safe and appropriate here.
     */
    public function cancel(Request $request, FamilyInvitation $invitation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($invitation->primary_account_id === $user->id, 403);

        $familyMember = $invitation->familyMember;

        AuditLog::record('family_invitation_cancelled', 'FamilyInvitation', $invitation->id, $user->id, $familyMember?->id);

        $invitation->delete();
        $familyMember?->forceDelete();

        return back()->with('status', 'invite-cancelled');
    }
}
