<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use App\Services\Qr\QrCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IdCard extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'card_number',
        'photo_path',
        'qr_code_path',
        'issued_at',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'card_number' => ['required', 'string', 'max:20'],
            'issued_at' => ['required', 'date'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public static function generateCardNumber(): string
    {
        do {
            $candidate = 'IDC-'.Str::upper(Str::random(10));
        } while (static::where('card_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Issues a fresh id_cards row for a family member — used both for the
     * very first card and for "Reissue card" (same operation: any
     * previously active card is deactivated rather than overwritten, so the
     * full issuance history is preserved; a brand new card_number and QR
     * mean a lost card/printout can never be reused to look active again).
     * The QR encodes the actual public emergency-card URL directly (not
     * JSON) so any generic phone camera opens it straight away.
     */
    public static function generateCard(FamilyMember $familyMember): self
    {
        static::where('family_member_id', $familyMember->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $cardNumber = static::generateCardNumber();

        $qrPath = app(QrCodeService::class)->store(
            url('/emergency/'.$cardNumber),
            'id-cards',
        );

        return static::create([
            'family_member_id' => $familyMember->id,
            'card_number' => $cardNumber,
            'photo_path' => $familyMember->photo_path,
            'qr_code_path' => $qrPath,
            'issued_at' => now(),
            'is_active' => true,
        ]);
    }
}
