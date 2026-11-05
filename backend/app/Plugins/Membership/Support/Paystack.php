<?php

namespace App\Plugins\Membership\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Paystack client: start a checkout, verify it, check webhook signatures.
 * Keys are per site (Membership → Settings). https://paystack.com/docs/api/transaction/
 */
class Paystack
{
    public const BASE_URL = 'https://api.paystack.co';

    /** Currencies Paystack accepts */
    public const CURRENCIES = ['GHS' => 'Ghana cedi (GHS)', 'NGN' => 'Naira (NGN)', 'USD' => 'US dollar (USD)', 'ZAR' => 'Rand (ZAR)', 'KES' => 'Kenyan shilling (KES)'];

    public function __construct(private ?string $secretKey) {}

    public function isConfigured(): bool
    {
        return filled($this->secretKey);
    }

    /**
     * Start a payment. Returns the Paystack page to send the payer to.
     */
    public function initialize(string $email, float $amount, string $currency, string $reference, string $callbackUrl, array $metadata = []): string
    {
        $response = $this->request()->post('/transaction/initialize', [
            'email' => $email,
            // Paystack amounts are in the smallest unit (pesewas, kobo, cents)
            'amount' => (int) round($amount * 100),
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException('Paystack could not start the payment: ' . ($response->json('message') ?? $response->status()));
        }

        return $response->json('data.authorization_url');
    }

    /**
     * Ask Paystack how a payment ended.
     *
     * @return array{status: string, amount: float, currency: string}|null null if Paystack has no such payment
     */
    public function verify(string $reference): ?array
    {
        $response = $this->request()->get('/transaction/verify/' . rawurlencode($reference));

        if ($response->status() === 404 || ($response->successful() && ! $response->json('status'))) {
            return null;
        }
        if (! $response->successful()) {
            throw new RuntimeException('Paystack could not be reached to check the payment.');
        }

        return [
            'status' => (string) $response->json('data.status'),
            'amount' => ((int) $response->json('data.amount')) / 100,
            'currency' => (string) $response->json('data.currency'),
        ];
    }

    /**
     * Webhooks are signed with HMAC-SHA512 of the raw body using the secret key.
     */
    public function validSignature(string $payload, ?string $signature): bool
    {
        return $this->isConfigured() && $signature
            && hash_equals(hash_hmac('sha512', $payload, $this->secretKey), $signature);
    }

    private function request()
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Paystack keys have not been set up for this site.');
        }

        return Http::baseUrl(self::BASE_URL)->withToken($this->secretKey)->acceptJson()->timeout(20);
    }
}
