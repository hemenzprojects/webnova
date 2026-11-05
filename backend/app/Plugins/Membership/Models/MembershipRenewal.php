<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A renewal a member started in the portal (Renew now): one membership year,
 * paid online in attempts like an application.
 */
class MembershipRenewal extends Model
{
    use Concerns\HasPaymentAttempts;

    protected $fillable = [
        'reference', 'member_account_id', 'membership_type_id', 'year', 'amount', 'currency',
        'payment_status', 'payment_reference', 'payment_provider', 'gateway_order_id', 'gateway_orders', 'paid_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'gateway_orders' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $renewal) {
            // e.g. REN-2026-7KQ2XW: readable on receipts, not guessable
            $renewal->reference ??= 'REN-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
        });
    }

    public function memberAccount(): BelongsTo
    {
        return $this->belongsTo(MemberAccount::class);
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(MembershipPayment::class);
    }
}
