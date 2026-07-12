<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
     * Issues a fresh id_cards row for a family member, snapshotting their
     * current photo and generating a QR code that encodes the unique_health_id
     * plus a verification URL. Any previously active card is deactivated
     * rather than overwritten, so the full issuance history is preserved.
     */
    public static function generateCard(FamilyMember $familyMember): self
    {
        $verificationUrl = url('/verify/'.$familyMember->unique_health_id);
        $qrPayload = json_encode([
            'unique_health_id' => $familyMember->unique_health_id,
            'verify_url' => $verificationUrl,
        ]);

        $qrPath = 'id-cards/qr-'.Str::lower(Str::random(16)).'.svg';
        Storage::disk('local')->put($qrPath, QrCode::format('svg')->size(300)->generate($qrPayload));

        static::where('family_member_id', $familyMember->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return static::create([
            'family_member_id' => $familyMember->id,
            'card_number' => static::generateCardNumber(),
            'photo_path' => $familyMember->photo_path,
            'qr_code_path' => $qrPath,
            'issued_at' => now(),
            'is_active' => true,
        ]);
    }
}
