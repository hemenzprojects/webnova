<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\MembershipPlugin;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipPayment;
use App\Plugins\Membership\Models\MembershipRenewal;
use App\Plugins\PluginManager;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Yearly renewals (Membership, Renewals feature). The membership year is the
 * calendar year; its fee is due on a set day (1 March by default). A payment
 * for a year, whenever made, pays that year. With the defaults:
 *
 * - due:       not paid yet, before 1 March (a reminder goes out a month before)
 * - overdue:   from 1 March; an overdue notice on 1 April
 * - suspended: from 1 June (3 months), members' area closed until they pay
 * - forfeited: once 2 whole membership years have gone unpaid
 *
 * Paying the current year's fee puts a member back in good standing. Categories
 * without a fee (Honorary…) are always in good standing; a corporate member's
 * seats stand or fall with the corporate member.
 */
class Renewals
{
    public static function enabled(): bool
    {
        return app(PluginManager::class)->featureIsActive('membership', MembershipPlugin::FEATURE_RENEWALS);
    }

    public static function dueDate(int $year): CarbonImmutable
    {
        $month = max(1, min(12, (int) MembershipSettings::get('renewal_due_month')));
        $day = max(1, min(CarbonImmutable::create($year, $month)->daysInMonth, (int) MembershipSettings::get('renewal_due_day')));

        return CarbonImmutable::create($year, $month, $day)->startOfDay();
    }

    /** Where the member stands today (or on $today); always "paid" while the feature is off */
    public static function standing(MemberAccount $account, ?CarbonInterface $today = null): Standing
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $currency = (string) MembershipSettings::get('currency');

        if (! self::enabled()) {
            return new Standing(Standing::PAID, null, null, null, 0, $currency);
        }

        // A seat: as its corporate member stands, with nothing to pay itself
        if ($account->sponsor_account_id && ($sponsor = $account->sponsor) && $sponsor->id !== $account->id) {
            $theirs = self::standing($sponsor, $today);

            return new Standing($theirs->state, $theirs->paidYear, $theirs->unpaidYear, null, 0, $currency, coveredBy: $sponsor->name);
        }

        $type = $account->membershipType;
        if (! $type || $type->isExempt()) {
            return new Standing(Standing::EXEMPT, $account->paidYear(), null, null, 0, $currency);
        }

        $year = $today->year;
        $amount = (float) $type->price;
        $paidYear = $account->paidYear();

        if ($paidYear !== null && $paidYear >= $year) {
            // Paid up: next year's fee can be paid from the early-renewal month
            $early = CarbonImmutable::create($year, max(1, min(12, (int) MembershipSettings::get('renewal_early_month'))), 1);
            $renewYear = $paidYear === $year && $today->gte($early) ? $year + 1 : null;

            return new Standing(Standing::PAID, $paidYear, null, $renewYear, $amount, $currency, self::dueDate($year + 1));
        }

        // The first year not paid for: the one after the last paid, or the year they joined
        $joined = CarbonImmutable::instance($account->created_at ?? $today)->startOfDay();
        $unpaidYear = $paidYear !== null ? $paidYear + 1 : $joined->year;
        // Someone added after the due date without paying (e.g. by an admin) has a month from the day they joined
        $dueDate = self::dueDate($unpaidYear);
        if ($paidYear === null && $joined->gt($dueDate)) {
            $dueDate = $joined->addMonthNoOverflow();
        }

        $noticeOn = $dueDate->addMonthsNoOverflow(max(0, (int) MembershipSettings::get('renewal_notice_months')));
        $suspendOn = $dueDate->addMonthsNoOverflow(max(0, (int) MembershipSettings::get('renewal_suspend_months')));
        // After N whole membership years unpaid: 1 January of the year after the Nth
        $forfeitYears = (int) MembershipSettings::get('renewal_forfeit_years');
        $forfeitOn = $forfeitYears > 0 ? CarbonImmutable::create($unpaidYear + $forfeitYears, 1, 1) : null;

        $state = match (true) {
            $forfeitOn !== null && $today->gte($forfeitOn) => Standing::FORFEITED,
            $today->gte($suspendOn) => Standing::SUSPENDED,
            $today->gte($dueDate) => Standing::OVERDUE,
            default => Standing::DUE,
        };

        // A renewal pays the current year (earlier unpaid years are not charged)
        return new Standing($state, $paidYear, $unpaidYear, $year, $amount, $currency, $dueDate, $noticeOn, $suspendOn, $forfeitOn);
    }

    /** Store the standing for lists and filters (Members); the members' area uses standing() itself */
    public static function refresh(MemberAccount $account): Standing
    {
        $standing = self::standing($account);
        if ($account->standing !== $standing->state) {
            $account->forceFill(['standing' => $standing->state])->saveQuietly();
        }
        // Seats follow their corporate member
        if ($account->seats()->exists()) {
            $account->seats()->get()->each(fn (MemberAccount $seat) => self::refresh($seat));
        }

        return $standing;
    }

    /**
     * Start paying the next year's fee online. Returns where to send the member,
     * or null when online payment is not set up.
     *
     * @return array{renewal: MembershipRenewal, payment_url: ?string}
     *
     * @throws ValidationException when there is nothing to pay
     */
    public function start(MemberAccount $account, string $siteUrl): array
    {
        $standing = self::standing($account);
        if (! $standing->canRenew()) {
            throw ValidationException::withMessages(['renewal' => 'There is nothing to pay at the moment.']);
        }

        // One open renewal per member and year, reused for another attempt
        $renewal = $account->renewals()->where('year', $standing->renewYear)->where('payment_status', '!=', 'paid')->latest()->first()
            ?? $account->renewals()->create([
                'membership_type_id' => $account->membership_type_id,
                'year' => $standing->renewYear,
                'amount' => $standing->amount,
                'currency' => $standing->currency,
            ]);
        // The fee may have changed since an earlier attempt
        $renewal->update(['amount' => $standing->amount, 'currency' => $standing->currency, 'membership_type_id' => $account->membership_type_id]);

        $url = app(OnlinePayments::class)->start(
            $renewal,
            $account->email,
            (float) $renewal->amount,
            $renewal->currency,
            rtrim($siteUrl, '/') . '/account/payments?renewal=' . urlencode($renewal->reference),
            ['renewal' => $renewal->reference, 'member_id' => $account->member_number, 'year' => $renewal->year],
        );

        return ['renewal' => $renewal->refresh(), 'payment_url' => $url];
    }

    /** Ask the provider about a renewal (an attempt, or the latest) and record the payment once paid */
    public function confirm(MembershipRenewal $renewal, ?string $paymentReference = null): MembershipRenewal
    {
        $paymentReference ??= $renewal->payment_reference;
        if ($renewal->payment_status !== 'paid') {
            $payments = app(OnlinePayments::class);
            $result = $payments->check($renewal, $paymentReference);

            if ($payments->paidInFull($result, (float) $renewal->amount, $renewal->currency)) {
                $renewal->update(['payment_status' => 'paid', 'paid_at' => now(), 'payment_reference' => $paymentReference]);
            } elseif ($result && $paymentReference === $renewal->payment_reference && $result['status'] === 'failed') {
                $renewal->update(['payment_status' => 'failed']);
            }
        }

        if ($renewal->payment_status === 'paid') {
            $this->paid($renewal);
        }

        return $renewal->refresh();
    }

    /** For Paystack webhooks: false if the payment is not for a renewal */
    public function confirmByPaymentReference(string $paymentReference): bool
    {
        $renewal = MembershipRenewal::where('payment_reference', $paymentReference)
            ->orWhere('reference', \Illuminate\Support\Str::beforeLast($paymentReference, '-'))
            ->first();
        if (! $renewal) {
            return false;
        }
        $this->confirm($renewal, $paymentReference);

        return true;
    }

    /** A paid renewal: its payment and receipt (once), and the member's new standing */
    private function paid(MembershipRenewal $renewal): void
    {
        $payment = app(Payments::class)->recordForRenewal($renewal);
        $this->sendReceipt($payment);
        self::refresh($renewal->memberAccount);
    }

    /** A renewal recorded by an admin (bank transfer, cash…), with its receipt */
    public function recordManual(MemberAccount $account, array $data, bool $sendReceipt = true): MembershipPayment
    {
        $payment = app(Payments::class)->recordManual(
            $account,
            (int) $data['year'],
            (float) $data['amount'],
            (string) $data['method'],
            filled($data['reference'] ?? null) ? trim($data['reference']) : null,
            CarbonImmutable::parse($data['paid_at']),
            filled($data['notes'] ?? null) ? trim($data['notes']) : null,
        );
        if ($sendReceipt) {
            $this->sendReceipt($payment);
        }
        self::refresh($account);

        return $payment;
    }

    private function sendReceipt(MembershipPayment $payment): void
    {
        if ($payment->emailed_at) {
            return;
        }
        try {
            if (app(MembershipMailer::class)->receipt($payment)) {
                $payment->forceFill(['emailed_at' => now()])->save();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Seats a (corporate) member may give out, and how many are used */
    public static function seats(MemberAccount $account): array
    {
        $type = $account->membershipType;
        $included = $account->sponsor_account_id || ! $type ? 0 : (int) $type->included_seats;

        return ['included' => $included, 'used' => $included ? $account->seats()->count() : 0, 'type' => $type?->seatType?->name];
    }

    /**
     * Give one of a corporate member's seats to someone: a new member account
     * (with the welcome email), or an existing member who is not paying for
     * themselves through another corporate member.
     *
     * @throws ValidationException
     */
    public function addSeat(MemberAccount $sponsor, string $name, string $email): MemberAccount
    {
        $seats = self::seats($sponsor);
        if ($seats['used'] >= $seats['included']) {
            throw ValidationException::withMessages(['email' => $seats['included']
                ? "All {$seats['included']} included seats are in use. Remove one first."
                : 'This membership does not include seats.']);
        }
        $email = strtolower(trim($email));
        if ($email === strtolower($sponsor->email)) {
            throw ValidationException::withMessages(['email' => 'Give the seat to someone else: this is your own email.']);
        }

        $existing = MemberAccount::where('email', $email)->first();
        if ($existing) {
            if ($existing->sponsor_account_id) {
                throw ValidationException::withMessages(['email' => 'This member already has a seat of a corporate member.']);
            }
            if ($existing->seats()->exists() || ($existing->membershipType?->included_seats ?? 0) > 0) {
                throw ValidationException::withMessages(['email' => 'A corporate member cannot take a seat.']);
            }
            $existing->update(['sponsor_account_id' => $sponsor->id]);
            self::refresh($existing);

            return $existing;
        }

        $accounts = app(MemberAccounts::class);
        $seat = $accounts->create([
            'name' => trim($name),
            'email' => $email,
            'membership_type_id' => $sponsor->membershipType?->seat_type_id,
            'sponsor_account_id' => $sponsor->id,
        ]);
        $accounts->sendWelcome($seat);
        self::refresh($seat);

        return $seat;
    }

    /** The seat is freed; the member keeps their account and pays for themselves from now on */
    public function removeSeat(MemberAccount $sponsor, MemberAccount $seat): void
    {
        abort_unless($seat->sponsor_account_id === $sponsor->id, 404);
        $seat->update(['sponsor_account_id' => null]);
        self::refresh($seat);
    }
}
