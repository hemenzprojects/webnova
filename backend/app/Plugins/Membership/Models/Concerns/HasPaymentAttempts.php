<?php

namespace App\Plugins\Membership\Models\Concerns;

/**
 * Something paid online in attempts ({reference}-1, -2…): an application or a
 * renewal. Columns: reference, payment_reference (latest attempt),
 * payment_provider, gateway_order_id, gateway_orders (every attempt).
 */
trait HasPaymentAttempts
{
    /**
     * Which provider a payment attempt went to, and its order id there (N-Genius
     * needs it). gateway_orders keeps every attempt: {reference: {provider, order_id}}.
     *
     * @return array{provider: string, order_id: ?string}
     */
    public function paymentAttempt(?string $reference = null): array
    {
        $reference ??= $this->payment_reference;
        $entry = ($this->gateway_orders ?? [])[$reference] ?? null;

        if (is_array($entry)) {
            return ['provider' => $entry['provider'] ?? ($this->payment_provider ?: 'paystack'), 'order_id' => $entry['order_id'] ?? null];
        }
        // Rows from before attempts kept their provider: an order id alone was a Stanbic one
        if (is_string($entry)) {
            return ['provider' => 'stanbic', 'order_id' => $entry];
        }

        return [
            'provider' => $this->payment_provider ?: 'paystack',
            'order_id' => $reference === $this->payment_reference ? $this->gateway_order_id : null,
        ];
    }

    /** An attempt of this payment (not a reference an admin typed) */
    public function isOnlineAttempt(?string $reference = null): bool
    {
        $reference ??= $this->payment_reference;

        return $reference !== null && str_starts_with($reference, $this->reference . '-');
    }
}
