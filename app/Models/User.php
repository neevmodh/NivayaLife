<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'has_password',
        'google_id',
        'avatar',
        'phone',
        'phone_country_code',
        'avatar_path',
        'theme_preference',
        'onboarding_dismissed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'onboarding_dismissed_at' => 'datetime',
            'is_admin' => 'boolean',
            'has_password' => 'boolean',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Family members this user created and owns (the family circle they administer).
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'primary_account_id');
    }

    /**
     * The single family_member profile this user is themselves linked to
     * (set when they accept an invitation and get their own login).
     */
    public function linkedFamilyMember(): HasOne
    {
        return $this->hasOne(FamilyMember::class, 'linked_user_id');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(FamilyInvitation::class, 'primary_account_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function auditLogEntries(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function uploadedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'uploaded_by_user_id');
    }

    /**
     * Sharing permissions other (linked) family members have granted to this user.
     */
    public function receivedSharingPermissions(): HasMany
    {
        return $this->hasMany(SharingPermission::class, 'granted_to_user_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Every user needs a "self" family_members row to have a dashboard/
     * profile at all. Accounts created before the registration wizard
     * existed (or via any other path that skips it) won't have one yet —
     * this provisions a minimal record on first access rather than 404ing.
     */
    public function ensureLinkedFamilyMember(): FamilyMember
    {
        if ($this->relationLoaded('linkedFamilyMember') && $this->linkedFamilyMember !== null) {
            return $this->linkedFamilyMember;
        }

        // firstOrCreate rather than a `?? create()` on the relation: reading
        // the relation caches a null on this instance, so a second call in the
        // same request would try to insert again and trip the unique index on
        // linked_user_id. Keyed on linked_user_id, which is exactly that index.
        $member = FamilyMember::firstOrCreate(
            ['linked_user_id' => $this->id],
            [
                'primary_account_id' => $this->id,
                'relation' => 'self',
                'full_name' => $this->name,
                'access_type' => 'linked',
                'status' => 'active',
            ]
        );

        // Keep the cached relation in step with what we just wrote.
        $this->setRelation('linkedFamilyMember', $member);

        return $member;
    }
}
