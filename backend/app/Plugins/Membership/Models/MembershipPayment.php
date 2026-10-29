<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A membership payment and its receipt. A payment made any day in a year
 * pays for that calendar year (`year`).
 */
class MembershipPayment extends Model
{
    public const PURPOSES = [
        'joining' => 'Membership (joining)',
        'renewal' => 'Membership renewal',
    ];

    public const METHODS = [
        'paystack' => 'Online (Paystack)',
        'bank' => 'Bank transfer',
        'cash' => 'Cash',
        'other' => 'Other',
    ];

    protected $fillable = [
        'receipt_number', 'member_account_id', 'membership_registration_id', 'membership_type_id', 'purpose', 'year',
        'amount', 'currency', 'method', 'reference', 'name', 'email', 'paid_at', 'emailed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'emailed_at' => 'datetime',
    ];

    public function memberAccount(): BelongsTo
    {
        return $this->belongsTo(MemberAccount::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(MembershipRegistration::class, 'membership_registration_id');
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    /** e.g. "USD 100.00" */
    public function formattedAmount(): string
    {
        return $this->currency . ' ' . number_format((float) $this->amount, 2);
    }

    /** What a receipt shows: on the website after paying, in the portal and in the receipt email */
    public function toReceiptArray(): array
    {
        return [
            'receipt_number' => $this->receipt_number,
            'member_id' => $this->memberAccount?->member_number,
            'name' => $this->name,
            'email' => $this->email,
            'membership_type' => $this->membershipType?->name,
            'purpose' => self::PURPOSES[$this->purpose] ?? $this->purpose,
            'year' => $this->year,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'method' => self::METHODS[$this->method] ?? $this->method,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'application_reference' => $this->registration?->reference,
        ];
    }
}
