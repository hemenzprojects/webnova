<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Let's Encrypt certificates for tenant domains, through Fly's Machines API.
 * Adding or removing a domain (App\Support\SyncDomainCertificate) calls
 * add()/remove(); the tenant page shows get() and the DNS records to set.
 *
 * Only on Fly with FLY_API_TOKEN set; elsewhere enabled() is false and every
 * call is a no-op.
 */
class FlyCertificates
{
    public function enabled(): bool
    {
        return filled(config('services.fly.token')) && filled(config('services.fly.app'));
    }

    /** Request a certificate. Fly issues it once the domain's DNS points here. */
    public function add(string $hostname): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $certificate = $this->client()->post('certificates/acme', ['hostname' => $hostname])->throw()->json();
        Cache::put($this->cacheKey($hostname), $certificate, now()->addMinute());

        return $certificate;
    }

    public function remove(string $hostname): void
    {
        if (! $this->enabled()) {
            return;
        }

        $response = $this->client()->delete('certificates/'.rawurlencode($hostname));
        if ($response->status() !== 404) {
            $response->throw();
        }
        Cache::forget($this->cacheKey($hostname));
    }

    /**
     * The certificate as Fly reports it (status, dns_requirements, …), or
     * null when there is none. Cached for a minute; $fresh asks Fly to
     * re-check the DNS now.
     */
    public function get(string $hostname, bool $fresh = false): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($fresh) {
            Cache::forget($this->cacheKey($hostname));
        }

        return Cache::remember($this->cacheKey($hostname), now()->addMinute(), function () use ($hostname, $fresh) {
            $path = 'certificates/'.rawurlencode($hostname);
            $response = $fresh ? $this->client()->post("{$path}/check") : $this->client()->get($path);

            return $response->status() === 404 ? null : $response->throw()->json();
        });
    }

    /** [label, colour] for the admin */
    public static function describe(?array $certificate): array
    {
        return match ($certificate['status'] ?? null) {
            null => ['No certificate', 'danger'],
            'active' => ['Active', 'success'],
            'pending_validation', 'pending' => ['Waiting for DNS', 'warning'],
            default => [str($certificate['status'])->replace('_', ' ')->ucfirst()->toString(), 'warning'],
        };
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.fly.url'), '/').'/apps/'.config('services.fly.app').'/')
            ->withToken(config('services.fly.token'))
            ->acceptJson()
            ->timeout(15);
    }

    private function cacheKey(string $hostname): string
    {
        return "fly-certificate:{$hostname}";
    }
}
