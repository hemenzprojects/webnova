<?php

namespace App\Plugins\Membership\Models;

use App\Models\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MembershipRegistration extends Model
{
    public const STATUSES = [
        'pending' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'not_required' => 'No payment',
    ];

    protected $fillable = [
        'reference', 'membership_type_id', 'name', 'email', 'answers', 'form_snapshot', 'form_version',
        'amount', 'currency', 'status', 'payment_status', 'payment_reference', 'paid_at', 'reviewed_at',
        'admin_notes', 'member_id',
    ];

    protected $casts = [
        'answers' => 'array',
        'form_snapshot' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $registration) {
            // e.g. MEM-2026-7KQ2XW: readable on receipts, not guessable
            $registration->reference ??= 'MEM-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
        });
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Answers as label => display value, in form order, using the form as it
     * was when this registration was submitted.
     *
     * @return array<string, string>
     */
    public function readableAnswers(): array
    {
        $out = [];
        foreach (\App\Plugins\Membership\Support\FormSchema::fields($this->form_snapshot ?? []) as $field) {
            if (! array_key_exists($field['key'], $this->answers ?? [])) {
                continue;
            }
            $value = $this->answers[$field['key']];
            if ($field['type'] === 'membership_type') {
                $value = $this->membershipType?->name ?? $value;
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_array($value)) {
                $value = implode(', ', $value);
            }
            $out[$field['label']] = (string) $value;
        }

        return $out;
    }
}
