<?php

namespace App\Plugins\Membership\Support\Gateways;

use App\Plugins\Membership\Support\Paystack;

/** Paystack (card and mobile money) behind the PaymentGateway interface */
class PaystackGateway implements PaymentGateway
{
    public function __construct(private Paystack $paystack) {}

    public function key(): string
    {
        return 'paystack';
    }

    public function isConfigured(): bool
    {
        return $this->paystack->isConfigured();
    }

    public function start(string $email, float $amount, string $currency, string $reference, string $returnUrl, array $metadata = []): array
    {
        return ['url' => $this->paystack->initialize($email, $amount, $currency, $reference, $returnUrl, $metadata), 'order_id' => null];
    }

    public function check(string $reference, ?string $orderId): ?array
    {
        $result = $this->paystack->verify($reference);
        if (! $result) {
            return null;
        }

        return [
            'status' => match ($result['status']) {
                'success' => 'success',
                'failed', 'abandoned', 'reversed' => 'failed',
                default => 'pending',
            },
            'amount' => $result['amount'],
            'currency' => $result['currency'],
        ];
    }
}
