<?php

namespace App\Plugins\Membership\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Starting and checking online payments for anything paid in attempts
 * (Models\Concerns\HasPaymentAttempts): applications and renewals. Each
 * attempt is "{reference}-{n}" and remembers the provider it went to.
 */
class OnlinePayments
{
    /**
     * Send the payer to the site's payment provider. Null when none is set up.
     *
     * @param  Model&\App\Plugins\Membership\Models\Concerns\HasPaymentAttempts  $payable
     */
    public function start(Model $payable, string $email, float $amount, string $currency, string $returnUrl, array $metadata = []): ?string
    {
        $gateway = MembershipSettings::gateway();
        if (! $gateway->isConfigured() || blank($email) || $payable->payment_status === 'paid') {
            return null;
        }

        // A fresh reference per attempt, so a failed payment can be retried
        $attempt = $payable->isOnlineAttempt() ? ((int) substr(strrchr($payable->payment_reference, '-'), 1)) + 1 : 1;
        $reference = "{$payable->reference}-{$attempt}";

        // Each attempt remembers its provider, so it is checked there even if the site switches later
        $attempts = $payable->gateway_orders ?? [];
        $attempts[$reference] = ['provider' => $gateway->key(), 'order_id' => null];
        $payable->update(['payment_reference' => $reference, 'payment_provider' => $gateway->key(), 'gateway_order_id' => null, 'gateway_orders' => $attempts]);

        $payment = $gateway->start($email, $amount, $currency, $reference, $returnUrl, $metadata);
        if ($payment['order_id']) {
            $attempts[$reference]['order_id'] = $payment['order_id'];
            $payable->update(['gateway_order_id' => $payment['order_id'], 'gateway_orders' => $attempts]);
        }

        return $payment['url'];
    }

    /**
     * Ask the provider about one attempt (the latest by default).
     *
     * @return array{status: string, amount: float, currency: string}|null
     */
    public function check(Model $payable, ?string $reference = null): ?array
    {
        $reference ??= $payable->payment_reference;
        if (! $reference || ! $payable->isOnlineAttempt($reference)) {
            return null;
        }

        // Checked with the provider this attempt was sent to; N-Genius looks payments up by its own order id
        $attempt = $payable->paymentAttempt($reference);

        return MembershipSettings::gateway($attempt['provider'])->check($reference, $attempt['order_id']);
    }

    /** Paid in full, in the right currency */
    public function paidInFull(?array $result, float $amount, string $currency): bool
    {
        return $result !== null
            && $result['status'] === 'success'
            && strtoupper($result['currency']) === strtoupper($currency)
            && $result['amount'] + 0.001 >= $amount;
    }
}
