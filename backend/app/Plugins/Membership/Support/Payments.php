<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipPayment;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipRenewal;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
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
        return $this->once('membership_registration_id', $registration->id, function () use ($registration, $account) {
            $paidAt = $registration->paid_at ?? now();

            return [
                'member_account_id' => $account?->id,
                'membership_registration_id' => $registration->id,
                'membership_type_id' => $registration->membership_type_id,
                'purpose' => 'joining',
                'year' => (int) $paidAt->format('Y'),
                'amount' => $registration->amount,
                'currency' => $registration->currency,
                // Online attempts are "{application reference}-{n}"; anything else was recorded by an admin
                'method' => $registration->isOnlineAttempt() ? $registration->paymentAttempt()['provider'] : 'other',
                'reference' => $registration->payment_reference,
                'gateway_reference' => $registration->paymentAttempt()['order_id'],
                'name' => $registration->name,
                'email' => $registration->email,
                'paid_at' => $paidAt,
            ];
        });
    }

    /** The payment of a renewal paid online, recorded once */
    public function recordForRenewal(MembershipRenewal $renewal): MembershipPayment
    {
        return $this->once('membership_renewal_id', $renewal->id, function () use ($renewal) {
            $account = $renewal->memberAccount;

            return [
                'member_account_id' => $account->id,
                'membership_renewal_id' => $renewal->id,
                'membership_type_id' => $renewal->membership_type_id,
                'purpose' => 'renewal',
                'year' => $renewal->year,
                'amount' => $renewal->amount,
                'currency' => $renewal->currency,
                'method' => $renewal->paymentAttempt()['provider'],
                'reference' => $renewal->payment_reference,
                'gateway_reference' => $renewal->paymentAttempt()['order_id'],
                'name' => $account->name,
                'email' => $account->email,
                'paid_at' => $renewal->paid_at ?? now(),
            ];
        });
    }

    /** A renewal paid by bank transfer, cash… and recorded by an admin */
    public function recordManual(MemberAccount $account, int $year, float $amount, string $method, ?string $reference, CarbonInterface $paidAt, ?string $notes = null): MembershipPayment
    {
        return $this->create(fn () => [
            'member_account_id' => $account->id,
            'membership_type_id' => $account->membership_type_id,
            'purpose' => 'renewal',
            'year' => $year,
            'amount' => $amount,
            'currency' => MembershipSettings::get('currency'),
            'method' => $method,
            'reference' => $reference,
            'name' => $account->name,
            'email' => $account->email,
            'notes' => $notes,
            'paid_at' => $paidAt,
        ]);
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

    /** The payment for this application or renewal: the existing one, or a new one */
    private function once(string $column, int $id, callable $attributes): MembershipPayment
    {
        return $this->create($attributes, fn () => MembershipPayment::where($column, $id)->lockForUpdate()->first());
    }

    /**
     * A new payment with the next receipt number. Two payments in the same
     * instant can pick the same number: the second tries the next one.
     */
    private function create(callable $attributes, ?callable $existing = null): MembershipPayment
    {
        for ($try = 1; ; $try++) {
            try {
                return DB::connection((new MembershipPayment)->getConnectionName())->transaction(function () use ($attributes, $existing) {
                    if ($existing && ($found = $existing())) {
                        return $found;
                    }
                    $values = $attributes();

                    return MembershipPayment::create($values + [
                        'receipt_number' => $this->nextReceiptNumber((int) $values['paid_at']->format('Y')),
                    ]);
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($try >= 3) {
                    throw $e;
                }
            }
        }
    }
}
