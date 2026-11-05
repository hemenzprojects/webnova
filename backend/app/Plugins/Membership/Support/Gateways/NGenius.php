<?php

namespace App\Plugins\Membership\Support\Gateways;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Card payments through N-Genius (Network International), the gateway behind
 * Stanbic Bank Ghana's merchant card payments. The payer pays on the bank's
 * hosted payment page. https://docs.ngenius-payments.com/reference/hosted-payment-page
 *
 * The bank gives each merchant an outlet reference, an API key and a realm
 * name; the API address differs between the sandbox and live.
 */
class NGenius implements PaymentGateway
{
    public const SANDBOX_URL = 'https://api-gateway.sandbox.stanbicbank.com.gh';

    public const DEFAULT_REALM = 'StanbicBankGhana';

    /** States of a paid order (PURCHASE = authorise and capture at once) */
    private const PAID = ['PURCHASED', 'CAPTURED'];

    private const FAILED = ['FAILED', 'DECLINED', 'CANCELLED', 'REVERSED', 'VOIDED'];

    public function __construct(
        private ?string $apiUrl,
        private ?string $outlet,
        private ?string $apiKey,
        private ?string $realm,
    ) {
        $this->apiUrl = rtrim(trim((string) $apiUrl), '/') ?: self::SANDBOX_URL;
        $this->realm = trim((string) $realm) ?: self::DEFAULT_REALM;
    }

    public function key(): string
    {
        return 'stanbic';
    }

    public function isConfigured(): bool
    {
        return filled($this->outlet) && filled($this->apiKey);
    }

    /**
     * Only https addresses with a public host name: the API key is sent to
     * this address, so it must not point at an IP or inside the platform.
     */
    public static function validApiUrl(?string $url): bool
    {
        $parts = parse_url(trim((string) $url));
        $host = strtolower((string) ($parts['host'] ?? ''));

        return ($parts['scheme'] ?? '') === 'https'
            && ! isset($parts['user']) && ! isset($parts['port'])
            && str_contains($host, '.')
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && ! str_ends_with($host, '.local') && ! str_ends_with($host, '.internal') && ! str_ends_with($host, 'localhost')
            && (bool) preg_match('/^[a-z0-9.-]+$/', $host);
    }

    public function isSandbox(): bool
    {
        return str_contains($this->apiUrl, 'sandbox');
    }

    public function start(string $email, float $amount, string $currency, string $reference, string $returnUrl, array $metadata = []): array
    {
        $body = json_encode([
                'action' => 'PURCHASE',
                // In the smallest unit (pesewas, cents)
                'amount' => ['currencyCode' => strtoupper($currency), 'value' => (int) round($amount * 100)],
                'emailAddress' => $email,
                'merchantOrderReference' => $reference,
                'merchantAttributes' => [
                    'redirectUrl' => $returnUrl,
                    'cancelUrl' => $returnUrl,
                    'cancelText' => 'Back to the website',
                    'skipConfirmationPage' => true,
                ],
                'merchantDefinedData' => array_map('strval', array_filter($metadata, 'filled')),
            ]);
        $response = $this->send(fn ($http) => $http
            ->withHeaders(['Accept' => 'application/vnd.ni-payment.v2+json'])
            ->withBody($body, 'application/vnd.ni-payment.v2+json')
            ->post($this->ordersUrl()));

        $url = $response->json('_links.payment.href');
        if (! $response->successful() || ! $url) {
            throw new RuntimeException('The bank could not start the payment: ' . $this->error($response));
        }

        return ['url' => $url, 'order_id' => (string) $response->json('reference')];
    }

    public function check(string $reference, ?string $orderId): ?array
    {
        if (blank($orderId)) {
            return null;
        }

        $response = $this->send(fn ($http) => $http
            ->withHeaders(['Accept' => 'application/vnd.ni-payment.v2+json'])
            ->get($this->ordersUrl() . '/' . rawurlencode($orderId)));

        if ($response->status() === 404) {
            return null;
        }
        if (! $response->successful()) {
            throw new RuntimeException('The bank could not be reached to check the payment: ' . $this->error($response));
        }
        // Our reference travels with the order: never accept another order's payment
        if ($response->json('merchantOrderReference') !== $reference) {
            return null;
        }

        $payments = collect($response->json('_embedded.payment') ?? []);
        $paid = $payments->first(fn ($p) => in_array($p['state'] ?? null, self::PAID, true));
        $latest = $paid ?? $payments->last();
        $state = (string) ($latest['state'] ?? '');

        return [
            'status' => $paid ? 'success' : (in_array($state, self::FAILED, true) ? 'failed' : 'pending'),
            'amount' => ((int) ($latest['amount']['value'] ?? $response->json('amount.value'))) / 100,
            'currency' => (string) ($latest['amount']['currencyCode'] ?? $response->json('amount.currencyCode')),
        ];
    }

    /** Signs in with the API key; true when the bank accepts it (for "Test connection") */
    public function testConnection(): bool
    {
        Cache::forget($this->tokenCacheKey());

        return filled($this->token());
    }

    /**
     * A request with the cached access token; if the bank says the token has
     * expired after all, once more with a new one.
     */
    private function send(callable $call)
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stanbic card payments have not been set up for this site.');
        }
        if (! self::validApiUrl($this->apiUrl)) {
            throw new RuntimeException('The Stanbic API address must be an https address with a host name.');
        }

        $response = $call(Http::withToken($this->token())->timeout(25));
        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey());
            $response = $call(Http::withToken($this->token())->timeout(25));
        }

        return $response;
    }

    /** Access tokens last about 5 minutes (expires_in); reused until a minute before they run out */
    private function token(): string
    {
        if (! self::validApiUrl($this->apiUrl)) {
            throw new RuntimeException('The Stanbic API address must be an https address with a host name.');
        }

        $cached = Cache::get($this->tokenCacheKey());
        if ($cached) {
            return $cached;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . trim((string) $this->apiKey),
            'Accept' => 'application/vnd.ni-identity.v1+json',
        ])->timeout(20)
            ->withBody(json_encode(['realmName' => $this->realm]), 'application/vnd.ni-identity.v1+json')
            ->post($this->apiUrl . '/identity/auth/access-token');

        $token = $response->json('access_token');
        if (! $response->successful() || ! $token) {
            throw new RuntimeException('The bank refused the API key, outlet or realm: ' . $this->error($response));
        }

        $seconds = (int) $response->json('expires_in') - 60;
        if ($seconds > 0) {
            Cache::put($this->tokenCacheKey(), $token, min($seconds, 600));
        }

        return $token;
    }

    private function tokenCacheKey(): string
    {
        return 'ngenius-token:' . hash('sha256', $this->apiUrl . '|' . $this->realm . '|' . $this->apiKey);
    }

    private function ordersUrl(): string
    {
        return $this->apiUrl . '/transactions/outlets/' . rawurlencode((string) $this->outlet) . '/orders';
    }

    private function error($response): string
    {
        $message = $response->json('message') ?? $response->json('errors.0.message') ?? $response->json('error_description');

        return $message ? (string) $message : 'HTTP ' . $response->status();
    }
}
