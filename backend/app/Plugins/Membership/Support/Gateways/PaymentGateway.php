<?php

namespace App\Plugins\Membership\Support\Gateways;

/**
 * An online payment provider for membership fees (Membership → Settings → Online payments).
 */
interface PaymentGateway
{
    /** e.g. "paystack"; also the payment method on receipts (MembershipPayment::METHODS) */
    public function key(): string;

    public function isConfigured(): bool;

    /**
     * Start a payment and return where to send the payer, and the provider's
     * own id for it when it has one (needed to check the payment later).
     *
     * @return array{url: string, order_id: ?string}
     */
    public function start(string $email, float $amount, string $currency, string $reference, string $returnUrl, array $metadata = []): array;

    /**
     * How a payment ended: status success / failed / pending, with what was paid.
     *
     * @return array{status: string, amount: float, currency: string}|null null if the provider has no such payment
     */
    public function check(string $reference, ?string $orderId): ?array;
}
