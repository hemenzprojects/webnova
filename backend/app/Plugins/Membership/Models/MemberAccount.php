<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'member_number', 'name', 'email', 'password', 'membership_type_id', 'membership_registration_id',
        'status', 'invited_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'invited_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $account) {
            $account->email = strtolower(trim($account->email));
        });
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(MembershipRegistration::class, 'membership_registration_id');
    }

    /** May open members-only pages */
    public function isActive(): bool
    {
        return $this->status === 'active';
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
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'is_active' => $this->isActive(),
            'member_since' => $this->created_at?->toDateString(),
        ];
    }
}
