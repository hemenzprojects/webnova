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

    public static function paystack(): Paystack
    {
        return new Paystack(self::get('paystack_secret_key'));
    }
}
