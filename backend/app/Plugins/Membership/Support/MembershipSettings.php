<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\PluginManager;

/**
 * The Membership plugin's settings for the current site, with defaults.
 * Stored (encrypted) on the tenant_plugins row.
 */
class MembershipSettings
{
    public const DEFAULTS = [
        'currency' => 'GHS',
        'require_approval' => true,
        'notification_email' => null,
        'success_message' => 'Thank you! Your registration has been received. We will be in touch by email.',
        'paystack_public_key' => null,
        'paystack_secret_key' => null,
        // Member accounts feature
        'member_number_prefix' => null,
        'mail_from_address' => null,
        'mail_from_name' => null,
        // Wording of the plugin's emails (MembershipEmails); null = defaults
        'emails' => null,
    ];

    public static function all(): array
    {
        return array_merge(self::DEFAULTS, app(PluginManager::class)->settings('membership'));
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }

    public static function save(array $values): void
    {
        app(PluginManager::class)->saveSettings('membership', array_merge(self::all(), $values));
    }

    /** Start of every Member ID, e.g. "APBA" in APBA-2026-0042. Defaults to the site's ID. */
    public static function memberNumberPrefix(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) self::get('member_number_prefix')));

        if ($prefix === '') {
            $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) tenant()?->getTenantKey()));
        }

        return $prefix !== '' ? $prefix : 'MEM';
    }

    /**
     * Sender of emails to members, e.g. ['membership@apba.org', 'APBA Membership'];
     * null for the platform's default sender.
     *
     * @return array{0: string, 1: ?string}|null
     */
    public static function mailFrom(): ?array
    {
        $address = trim((string) self::get('mail_from_address'));

        return $address !== '' ? [$address, trim((string) self::get('mail_from_name')) ?: null] : null;
    }

    /** The site's address for links in emails: the request's when there is one, else the tenant's first domain */
    public static function siteUrl(): string
    {
        if (! app()->runningInConsole() && request()->getHost()) {
            return request()->getSchemeAndHttpHost();
        }

        $domain = tenant()?->domains()->orderBy('id')->value('domain');

        return $domain ? 'https://' . $domain : config('app.url');
    }

    public static function paystack(): Paystack
    {
        return new Paystack(self::get('paystack_secret_key'));
    }
}
