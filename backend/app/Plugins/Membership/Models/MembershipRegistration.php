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
        'amount', 'currency', 'status', 'payment_status', 'payment_reference', 'payment_provider', 'gateway_order_id', 'gateway_orders', 'paid_at', 'reviewed_at', 'decision_reason',
        'consented_at', 'consent_version', 'admin_notes', 'member_id',
    ];

    protected $casts = [
        'answers' => 'array',
        'gateway_orders' => 'array',
        'form_snapshot' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'consented_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $registration) {
            // e.g. MEM-2026-7KQ2XW: readable on receipts, not guessable
            $registration->reference ??= 'MEM-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
        });

        // Without review (Membership settings), paying makes an applicant a member: approved in the
        // same save as the payment, however it is recorded (Paystack, "Mark as paid", a free membership)
        static::saving(function (self $registration) {
            if ($registration->status === 'pending'
                && in_array($registration->payment_status, ['paid', 'not_required'], true)
                && ! \App\Plugins\Membership\Support\MembershipSettings::get('require_approval')) {
                $registration->status = 'approved';
                $registration->reviewed_at ??= now();
            }
        });

        // Its documents go with it
        static::deleted(fn (self $registration) => app(\App\Plugins\Membership\Support\ApplicationFiles::class)->deleteFor($registration));

        // Approval and payment, however they happen (admin, Paystack, free membership): the member
        // account and the acceptance or welcome email (ApplicationFlow)
        // (created / updated rather than saved: wasRecentlyCreated stays true for the rest of the request)
        static::created(fn (self $registration) => app(\App\Plugins\Membership\Support\ApplicationFlow::class)->saved($registration, true));
        static::updated(function (self $registration) {
            if ($registration->wasChanged(['status', 'payment_status'])) {
                app(\App\Plugins\Membership\Support\ApplicationFlow::class)->saved($registration, false);
            }
        });
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MembershipPayment::class);
    }

    public function memberAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MemberAccount::class);
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
            } elseif (\App\Plugins\Membership\Support\ApplicationFiles::isFile($value)) {
                $value = $value['name'];
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
