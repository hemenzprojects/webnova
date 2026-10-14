<?php

namespace App\Plugins\Donations\Support;

use App\Plugins\Donations\Models\Donation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Takes a donation from the public form through payment.
 */
class Donor
{
    /**
     * Validate and store a donation, then start its payment.
     *
     * @return array{donation: Donation, payment_url: ?string}
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function submit(array $input, string $siteUrl): array
    {
        $titles = (array) DonationSettings::get('titles');

        $data = Validator::make($input, [
            'currency' => ['required', Rule::in(DonationSettings::currencies())],
            'amount' => ['required', 'numeric', 'min:' . (float) DonationSettings::get('minimum_amount'), 'max:10000000'],
            'title' => $titles ? ['required', Rule::in($titles)] : ['nullable'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9 +()\-]{6,}$/'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ], [
            'amount.min' => 'The smallest donation we can accept is :min.',
            'phone.regex' => 'Enter a valid phone number.',
        ], ['amount' => 'donation amount'])->validate();

        $donation = Donation::create($data + ['method' => 'online', 'payment_status' => 'unpaid']);

        try {
            $paymentUrl = $this->startPayment($donation, $siteUrl);
        } catch (Throwable $e) {
            // Paystack refused or could not be reached (often a currency the
            // account does not accept). Nothing was paid, so keep no record.
            report($e);
            $donation->delete();

            throw ValidationException::withMessages([
                'amount' => "Online payment could not be started in {$donation->currency}. Please try another currency, or try again in a moment.",
            ]);
        }

        // Without online payment this is a pledge: tell the admin now. Paid
        // donations are announced once the money has arrived.
        if (! $paymentUrl) {
            $this->notifyAdmin($donation);
        }

        return ['donation' => $donation, 'payment_url' => $paymentUrl];
    }

    /**
     * Send the donor to Paystack. Null when Paystack is not set up: the
     * donation stays "not received" as a pledge for the admin to follow up.
     */
    public function startPayment(Donation $donation, string $siteUrl): ?string
    {
        $paystack = DonationSettings::paystack();
        if (! $paystack->isConfigured() || ! $donation->email || $donation->payment_status === 'paid' || $donation->method !== 'online') {
            return null;
        }

        // A fresh reference per attempt, so a failed payment can be retried
        $attempt = $donation->payment_reference ? ((int) substr(strrchr($donation->payment_reference, '-'), 1)) + 1 : 1;
        $reference = "{$donation->reference}-{$attempt}";
        $donation->update(['payment_reference' => $reference]);

        return $paystack->initialize(
            $donation->email,
            (float) $donation->amount,
            $donation->currency,
            $reference,
            rtrim($siteUrl, '/') . '/donation/complete?reference=' . urlencode($donation->reference),
            ['donation' => $donation->reference, 'donor' => $donation->donor_name],
        );
    }

    /**
     * Ask Paystack about a payment attempt and record the result. Checks the
     * latest attempt unless told which one: an earlier attempt can still
     * succeed after the donor has started another.
     */
    public function confirmPayment(Donation $donation, ?string $paymentReference = null): Donation
    {
        $paymentReference ??= $donation->payment_reference;

        if ($donation->payment_status === 'paid' || $donation->method !== 'online' || ! $paymentReference) {
            return $donation;
        }

        $result = DonationSettings::paystack()->verify($paymentReference);
        if (! $result) {
            return $donation;
        }

        $paidInFull = $result['status'] === 'success'
            && strtoupper($result['currency']) === strtoupper($donation->currency)
            && $result['amount'] + 0.001 >= (float) $donation->amount;

        if ($paidInFull) {
            // The where() makes this safe when the webhook and the donor's return arrive together
            $justPaid = Donation::whereKey($donation->id)->where('payment_status', '!=', 'paid')
                ->update(['payment_status' => 'paid', 'paid_at' => now(), 'payment_reference' => $paymentReference]);
            if ($justPaid) {
                $this->notifyAdmin($donation->refresh());
            }
        } elseif ($paymentReference === $donation->payment_reference && in_array($result['status'], ['failed', 'abandoned', 'reversed'], true)) {
            $donation->update(['payment_status' => 'failed']);
        }

        return $donation->refresh();
    }

    /**
     * For Paystack webhooks: confirm the donation a payment belongs to.
     * False if the payment is not a donation.
     */
    public function confirmByPaymentReference(string $paymentReference): bool
    {
        // Attempts are "{donation reference}-{n}", so earlier attempts are found too
        $donation = Donation::where('method', 'online')
            ->where(fn ($query) => $query
                ->where('payment_reference', $paymentReference)
                ->orWhere('reference', Str::beforeLast($paymentReference, '-')))
            ->first();
        if (! $donation) {
            return false;
        }

        $this->confirmPayment($donation, $paymentReference);

        return true;
    }

    private function notifyAdmin(Donation $donation): void
    {
        $to = DonationSettings::get('notification_email');
        if (! $to) {
            return;
        }

        $received = $donation->payment_status === 'paid';

        try {
            Mail::raw(
                ($received ? 'A donation was received.' : 'A donation was pledged. No payment has been received yet.')
                . "\n\nReference: {$donation->reference}\nAmount: {$donation->amount_formatted}\n"
                . "Donor: {$donation->donor_name}\nEmail: {$donation->email}\nPhone: {$donation->phone}\n"
                . ($donation->comments ? "\nComments:\n{$donation->comments}\n" : ''),
                fn ($message) => $message->to($to)->subject(($received ? 'Donation received: ' : 'Donation pledged: ') . $donation->amount_formatted . " from {$donation->donor_name}")
            );
        } catch (Throwable $e) {
            // A mail problem must not lose the donation
            Log::warning('Donation notification email failed', ['reference' => $donation->reference, 'error' => $e->getMessage()]);
        }
    }
}
