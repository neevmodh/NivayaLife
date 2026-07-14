<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FamilyMember extends Model
{
    use HasValidation, SoftDeletes;

    protected $fillable = [
        'primary_account_id',
        'linked_user_id',
        'relation',
        'full_name',
        'date_of_birth',
        'gender',
        'blood_group',
        'height_cm',
        'weight_kg',
        'photo_path',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'pincode',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'access_type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'height_cm' => 'float',
            'weight_kg' => 'float',
        ];
    }

    public function rules(): array
    {
        return [
            'primary_account_id' => ['required', 'integer', 'exists:users,id'],
            'linked_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'relation' => ['required', 'in:self,spouse,father,mother,son,daughter,grandfather,grandmother,other'],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:tomorrow'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'height_cm' => ['nullable', 'numeric', 'min:30', 'max:280'],
            'weight_kg' => ['nullable', 'numeric', 'min:2', 'max:400'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'access_type' => ['required', 'in:linked,dependent'],
            'status' => ['required', 'in:active,invited,pending'],
        ];
    }

    protected function applyDefaults(): void
    {
        $this->unique_health_id ??= static::generateUniqueHealthId();
    }

    public static function generateUniqueHealthId(): string
    {
        do {
            $candidate = 'NVX-'.Str::upper(Str::random(8));
        } while (static::withTrashed()->where('unique_health_id', $candidate)->exists());

        return $candidate;
    }

    public function primaryAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_account_id');
    }

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(FamilyInvitation::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(Allergy::class);
    }

    public function chronicConditions(): HasMany
    {
        return $this->hasMany(ChronicCondition::class);
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function bmiLogs(): HasMany
    {
        return $this->hasMany(BmiLog::class);
    }

    public function healthMetrics(): HasMany
    {
        return $this->hasMany(HealthMetric::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function auditLogEntries(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function idCard(): HasOne
    {
        return $this->hasOne(IdCard::class)->ofMany('issued_at', 'max');
    }

    public function idCardHistory(): HasMany
    {
        return $this->hasMany(IdCard::class)->latest('issued_at');
    }

    public function sharingPermissions(): HasMany
    {
        return $this->hasMany(SharingPermission::class);
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function isLinked(): bool
    {
        return $this->access_type === 'linked' && $this->linked_user_id !== null;
    }

    public function isDependent(): bool
    {
        return $this->access_type === 'dependent';
    }

    /**
     * Whether the given user may view this member's records.
     *
     * Dependents have no login of their own, so the primary account always
     * has access. Linked members manage their own record once linked — the
     * primary account only regains visibility if an active sharing_permissions
     * grant exists. The linked user themselves always has access to their
     * own record.
     */
    public function hasGrantedAccessTo(User $user): bool
    {
        if ($this->isDependent() && $this->primary_account_id === $user->id) {
            return true;
        }

        if ($this->isLinked() && $this->linked_user_id === $user->id) {
            return true;
        }

        return $this->sharingPermissions()
            ->where('granted_to_user_id', $user->id)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * Whether the given user may edit this member's profile and health
     * records. Dependents can only be edited by the primary account that
     * owns them. Linked members manage their own record — another account
     * only gains edit rights via an explicit "full" scope sharing_permissions
     * grant; "reports_only"/"summary_only" grants remain view-only.
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($this->isDependent()) {
            return $this->primary_account_id === $user->id;
        }

        if ($this->linked_user_id === $user->id) {
            return true;
        }

        return $this->sharingPermissions()
            ->where('granted_to_user_id', $user->id)
            ->where('scope', 'full')
            ->whereNull('revoked_at')
            ->exists();
    }
}
