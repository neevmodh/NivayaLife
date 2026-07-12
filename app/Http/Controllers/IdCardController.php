<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Models\IdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IdCardController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $user->ensureLinkedFamilyMember();

        $familyMembers = FamilyMember::where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->orderByRaw("relation = 'self' desc")
            ->orderBy('full_name')
            ->get();

        $requestedId = $request->integer('member') ?: session('active_family_member_id');
        $active = $familyMembers->firstWhere('id', $requestedId)
            ?? $familyMembers->firstWhere('relation', 'self')
            ?? $familyMembers->first();

        $card = $active->idCard;

        if (! $card) {
            $card = IdCard::generateCard($active);
            $active->unsetRelation('idCard');
        }

        $qrDataUri = null;
        if ($card->qr_code_path && Storage::disk('local')->exists($card->qr_code_path)) {
            $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get($card->qr_code_path));
        }

        return view('id-card.show', [
            'active' => $active,
            'familyMembers' => $familyMembers,
            'card' => $card,
            'qrDataUri' => $qrDataUri,
        ]);
    }
}
