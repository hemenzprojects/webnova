<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A member's login to the website's member portal ("member" guard). Admin
 * logins are App\Models\User; the two are never mixed.
 */
class MemberAccount extends Authenticatable
{
    public const STATUSES = [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ];

    protected $fillable = [
        'member_number', 'name', 'email', 'password', 'membership_type_id', 'sponsor_account_id', 'membership_registration_id',
        'status', 'standing', 'invited_at', 'last_login_at', 'consented_at', 'consent_version',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'invited_at' => 'datetime',
        'last_login_at' => 'datetime',
        'consented_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $account) {
            $account->email = strtolower(trim($account->email));
        });
        // The database cascade would skip the documents' files
        static::deleting(fn (self $account) => $account->documents()->get()->each->delete());
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    /** The corporate member whose fee covers this member (a seat) */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sponsor_account_id');
    }

    /** The seats this (corporate) member has given out */
    public function seats(): HasMany
    {
        return $this->hasMany(self::class, 'sponsor_account_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(MemberPublication::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(MembershipRenewal::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(MembershipNotice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MembershipPayment::class);
    }

    /** The latest membership year paid for, e.g. 2026; null if none recorded */
    public function paidYear(): ?int
    {
        $year = $this->payments()->max('year');

        return $year ? (int) $year : null;
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberDocument::class);
    }

    /** Has agreed to the site's current privacy notice (always true when the site has none) */
    public function hasCurrentConsent(): bool
    {
        $version = \App\Plugins\Membership\Support\MembershipSettings::privacyVersion();

        return $version === 0 || (int) $this->consent_version >= $version;
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(MembershipRegistration::class, 'membership_registration_id');
    }

    /** Not suspended by an admin (Members → Suspend) */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * May open members-only pages and files: active, and (with the Renewals
     * feature) not suspended or forfeited for not paying.
     */
    public function canUseMemberArea(): bool
    {
        return $this->isActive() && \App\Plugins\Membership\Support\Renewals::standing($this)->canUseMemberArea();
    }

    /** Has set a password from the welcome email */
    public function hasPassword(): bool
    {
        return filled($this->getAuthPassword());
    }

    /** What the member portal shows about the signed-in member */
    public function toPortalArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'member_number' => $this->member_number,
            'membership_type' => $this->membershipType?->name,
            'status' => $this->status,
            // "Suspended (not paid)" while closed for not paying (Renewals), else the admin status
            'status_label' => $this->isActive() && ! $this->canUseMemberArea()
                ? \App\Plugins\Membership\Support\Renewals::standing($this)->label()
                : (self::STATUSES[$this->status] ?? $this->status),
            // Members-only pages and files are open to them (also false while suspended for not paying)
            'is_active' => $this->canUseMemberArea(),
            'member_since' => $this->created_at?->toDateString(),
            // Membership runs by calendar year: a payment any day in a year covers that year
            'paid_year' => $this->paidYear(),
            'current_year' => (int) now()->format('Y'),
            // The site's privacy notice changed (or was never agreed to): the portal asks again
            'needs_consent' => ! $this->hasCurrentConsent(),
            // Optional parts of the portal the site has switched on
            'features' => [
                'documents' => app(\App\Plugins\PluginManager::class)->featureIsActive('membership', \App\Plugins\Membership\MembershipPlugin::FEATURE_DOCUMENTS),
                'renewals' => \App\Plugins\Membership\Support\Renewals::enabled(),
                'publications' => \App\Plugins\Membership\Support\Publications::enabled(),
            ],
            // Renewals: paid, due, overdue, suspended… with what to pay (null while the feature is off)
            'standing' => \App\Plugins\Membership\Support\Renewals::enabled() ? \App\Plugins\Membership\Support\Renewals::standing($this)->toPortalArray() : null,
            // Seats a corporate membership includes (Our seats in the portal)
            'seats_included' => \App\Plugins\Membership\Support\Renewals::enabled() ? \App\Plugins\Membership\Support\Renewals::seats($this)['included'] : 0,
        ];
    }
}
