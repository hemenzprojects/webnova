<?php

namespace App\Plugins\Donations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Donation extends Model
{
    public const PAYMENT_STATUSES = [
        'unpaid' => 'Not received',
        'paid' => 'Received',
        'failed' => 'Failed',
    ];

    public const METHODS = [
        'online' => 'Online (Paystack)',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'mobile_money' => 'Mobile money',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    protected $fillable = [
        'reference', 'title', 'first_name', 'last_name', 'email', 'phone', 'comments', 'amount', 'currency',
        'method', 'payment_status', 'payment_reference', 'paid_at', 'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $donation) {
            // e.g. DON-2026-7KQ2XW: readable on receipts, not guessable
            $donation->reference ??= 'DON-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
        });
    }

    /** "Dr Ama Mensah" */
    public function getDonorNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->title, $this->first_name, $this->last_name])));
    }

    public function getAmountFormattedAttribute(): string
    {
        return $this->currency . ' ' . number_format((float) $this->amount, 2);
    }
}
