<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipPayment;
use App\Plugins\Membership\Models\MembershipRegistration;
use Illuminate\Support\Facades\DB;

/**
 * Membership payments and their receipt numbers (RCT-2026-0001: the year
 * paid and a running number that starts again each year).
 */
class Payments
{
    /**
     * The payment of a paid application, recorded once (a later save of the
     * same application returns the existing one).
     */
    public function recordForRegistration(MembershipRegistration $registration, ?MemberAccount $account = null): MembershipPayment
    {
        return DB::connection($registration->getConnectionName())->transaction(function () use ($registration, $account) {
            $existing = MembershipPayment::where('membership_registration_id', $registration->id)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $paidAt = $registration->paid_at ?? now();

            return MembershipPayment::create([
                'receipt_number' => $this->nextReceiptNumber((int) $paidAt->format('Y')),
                'member_account_id' => $account?->id,
                'membership_registration_id' => $registration->id,
                'membership_type_id' => $registration->membership_type_id,
                'purpose' => 'joining',
                'year' => (int) $paidAt->format('Y'),
                'amount' => $registration->amount,
                'currency' => $registration->currency,
                // Paystack attempts are "{application reference}-{n}"; anything else was recorded by an admin
                'method' => $registration->payment_reference && str_starts_with($registration->payment_reference, $registration->reference . '-') ? 'paystack' : 'other',
                'reference' => $registration->payment_reference,
                'name' => $registration->name,
                'email' => $registration->email,
                'paid_at' => $paidAt,
            ]);
        });
    }

    /** Payments of an application made before its member account existed now belong to it */
    public function attachToAccount(MembershipRegistration $registration, MemberAccount $account): void
    {
        MembershipPayment::where('membership_registration_id', $registration->id)
            ->whereNull('member_account_id')
            ->update(['member_account_id' => $account->id]);
    }

    public function nextReceiptNumber(int $year): string
    {
        $start = "RCT-{$year}-";
        $last = MembershipPayment::where('receipt_number', 'like', $start . '%')
            ->lockForUpdate()
            ->pluck('receipt_number')
            ->map(fn ($number) => (int) substr($number, strlen($start)))
            ->max() ?? 0;

        return $start . sprintf('%04d', $last + 1);
    }
}
