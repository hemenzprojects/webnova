<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Catches mistyped domains before a site or certificate is created for them:
 * looks the name up in public DNS (Google's DNS-over-HTTPS, so local /etc/hosts
 * entries do not count) and says whether it exists and where it points.
 */
class DomainCheck
{
    public const MISSING = 'missing';     // no such name: almost always a typo

    public const UNPOINTED = 'unpointed'; // exists, no address yet

    public const ELSEWHERE = 'elsewhere'; // exists, points to another server

    public const HERE = 'here';           // already points to the platform

    public const UNKNOWN = 'unknown';     // lookup failed

    /** @return array{state: string, ips: string[]} */
    public function check(string $domain): array
    {
        return Cache::remember("domain-check:{$domain}", now()->addMinutes(2), function () use ($domain) {
            $a = $this->resolve($domain, 'A');
            $aaaa = $this->resolve($domain, 'AAAA');

            if ($a === null || $aaaa === null) {
                return ['state' => self::UNKNOWN, 'ips' => []];
            }
            if ($a === false && $aaaa === false) {
                return ['state' => self::MISSING, 'ips' => []];
            }

            $ips = array_merge($a ?: [], $aaaa ?: []);
            if (! $ips) {
                return ['state' => self::UNPOINTED, 'ips' => []];
            }

            $platform = $this->platformIps();

            return ['state' => $platform && array_intersect($ips, $platform) ? self::HERE : self::ELSEWHERE, 'ips' => $ips];
        });
    }

    /** [short label, colour, icon, explanation] for the admin */
    public static function describe(array $result): array
    {
        $ips = implode(', ', array_slice($result['ips'], 0, 2));

        return match ($result['state']) {
            self::MISSING => ['Domain not found', 'danger', 'heroicon-m-x-circle', 'This domain does not exist. Check the spelling, or have the client register it first.'],
            self::UNPOINTED => ['Domain exists', 'info', 'heroicon-m-information-circle', 'The domain is registered but has no web address yet. Give the client the DNS records after saving.'],
            self::ELSEWHERE => ['Domain exists', 'warning', 'heroicon-m-exclamation-triangle', "The domain is registered and currently points to another server ({$ips}). The site will appear here once the client changes its DNS records."],
            self::HERE => ['Points to the platform', 'success', 'heroicon-m-check-circle', 'The domain already points to the platform, so its certificate can be issued straight away.'],
            default => ['Could not check', 'gray', 'heroicon-m-question-mark-circle', 'The domain could not be looked up just now. Check the spelling carefully.'],
        };
    }

    /**
     * The name's addresses; false when the name does not exist at all,
     * null when the lookup failed. A CNAME is followed.
     */
    private function resolve(string $name, string $type): array|false|null
    {
        try {
            $response = Http::timeout(5)->acceptJson()
                ->get('https://dns.google/resolve', ['name' => $name, 'type' => $type]);
        } catch (\Throwable) {
            return null;
        }

        $status = $response->json('Status');
        if (! $response->ok() || $status === null) {
            return null;
        }
        if ($status === 3) { // NXDOMAIN
            return false;
        }
        if ($status !== 0) {
            return null;
        }

        $code = $type === 'A' ? 1 : 28;

        return collect($response->json('Answer', []))->where('type', $code)->pluck('data')->values()->all();
    }

    /** The addresses of {app}.fly.dev, which every tenant domain should point to */
    private function platformIps(): array
    {
        $app = config('services.fly.app');
        if (! $app) {
            return [];
        }

        return Cache::remember('domain-check:platform-ips', now()->addHour(), fn () => array_merge(
            $this->resolve("{$app}.fly.dev", 'A') ?: [],
            $this->resolve("{$app}.fly.dev", 'AAAA') ?: [],
        ));
    }
}
