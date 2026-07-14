<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FamilyMember;
use App\Models\IdCard;
use App\Services\Qr\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IdCardController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActive($request, $user);

        $card = $active->idCard;

        if (! $card) {
            $card = IdCard::generateCard($active);
            $active->unsetRelation('idCard');
        }

        return view('id-card.show', [
            'active' => $active,
            'familyMembers' => $this->familyMembers($user),
            'card' => $card,
            'qrDataUri' => app(QrCodeService::class)->dataUriFromPath($card->qr_code_path),
        ]);
    }

    /** Lost card, printout, or the info just needs a hard reset — new card_number, new QR, old card kept (inactive) for history. */
    public function reissue(Request $request): RedirectResponse
    {
        $user = $request->user();
        $active = $this->resolveActive($request, $user);

        abort_unless($active->canBeEditedBy($user), 403);

        $card = IdCard::generateCard($active);

        AuditLog::record('id_card_reissued', 'IdCard', $card->id, $user->id, $active->id);

        return redirect()->route('id-card.show', ['member' => $active->id])->with('status', 'card-reissued');
    }

    /** Instant on/off — no new card_number, for when a lost phone/printout just needs to stop working right now. */
    public function toggleActive(Request $request, IdCard $idCard): RedirectResponse
    {
        $user = $request->user();
        abort_unless($idCard->familyMember->canBeEditedBy($user), 403);

        $reactivating = ! $idCard->is_active;

        if ($reactivating) {
            // Reactivating an older card (e.g. undoing an accidental
            // deactivation) must never leave two cards active at once —
            // the same invariant IdCard::generateCard() keeps on reissue.
            IdCard::where('family_member_id', $idCard->family_member_id)
                ->where('id', '!=', $idCard->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $idCard->update(['is_active' => $reactivating]);

        AuditLog::record(
            $idCard->is_active ? 'id_card_activated' : 'id_card_deactivated',
            'IdCard',
            $idCard->id,
            $user->id,
            $idCard->family_member_id
        );

        return redirect()->route('id-card.show', ['member' => $idCard->family_member_id])
            ->with('status', $idCard->is_active ? 'card-activated' : 'card-deactivated');
    }

    private function resolveActive(Request $request, $user): FamilyMember
    {
        $user->ensureLinkedFamilyMember();

        $familyMembers = $this->familyMembers($user);

        $requestedId = $request->integer('member') ?: session('active_family_member_id');

        return $familyMembers->firstWhere('id', $requestedId)
            ?? $familyMembers->firstWhere('relation', 'self')
            ?? $familyMembers->first();
    }

    private function familyMembers($user)
    {
        return FamilyMember::where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->orderByRaw("relation = 'self' desc")
            ->orderBy('full_name')
            ->get();
    }
}
