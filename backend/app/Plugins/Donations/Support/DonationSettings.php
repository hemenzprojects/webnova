<?php

namespace App\Plugins\Donations\Support;

use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Paystack;
use App\Plugins\PluginManager;

/**
 * The Donations plugin's settings for the current site, with defaults.
 * Stored (encrypted) on the tenant_plugins row.
 */
class DonationSettings
{
    public const DEFAULTS = [
        // Offered in the form's currency dropdown; the first is preselected
        'currencies' => ['GHS', 'USD'],
        'minimum_amount' => 1,
        'titles' => ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Rev'],
        'notification_email' => null,
        'success_message' => 'Thank you for your generous donation! A receipt has been sent to your email by Paystack.',
        'pledge_message' => 'Thank you! We have recorded your pledge and will contact you about how to send your donation.',
        'paystack_public_key' => null,
        'paystack_secret_key' => null,
    ];

    /** How each currency reads in the public form's dropdown */
    public const CURRENCY_LABELS = [
        'GHS' => 'Cedi (GH₵)',
        'NGN' => 'Naira (₦)',
        'USD' => 'Dollar ($)',
        'ZAR' => 'Rand (R)',
        'KES' => 'Shilling (KSh)',
    ];

    public static function all(): array
    {
        return array_merge(self::DEFAULTS, app(PluginManager::class)->settings('donations'));
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }

    public static function save(array $values): void
    {
        app(PluginManager::class)->saveSettings('donations', array_merge(self::all(), $values));
    }

    /** @return string[] currency codes, never empty */
    public static function currencies(): array
    {
        $currencies = array_values(array_intersect((array) self::get('currencies'), array_keys(Paystack::CURRENCIES)));

        return $currencies ?: [self::DEFAULTS['currencies'][0]];
    }

    public static function defaultCurrency(): string
    {
        return self::currencies()[0];
    }

    /**
     * True when no keys are saved here and the Membership plugin's are used
     * instead, so a site with one Paystack account enters its keys once.
     */
    public static function usesMembershipKeys(): bool
    {
        return blank(self::get('paystack_secret_key'))
            && app(PluginManager::class)->isActive('membership')
            && filled(MembershipSettings::get('paystack_secret_key'));
    }

    public static function paystack(): Paystack
    {
        return new Paystack(self::usesMembershipKeys()
            ? MembershipSettings::get('paystack_secret_key')
            : self::get('paystack_secret_key'));
    }
}
