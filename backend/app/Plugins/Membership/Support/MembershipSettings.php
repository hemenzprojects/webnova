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
        // How Member IDs are numbered: MEMBER_ID_FORMATS
        'member_number_format' => 'yearly',
        'mail_from_address' => null,
        'mail_from_name' => null,
        // Wording of the plugin's emails (MembershipEmails); null = defaults
        'emails' => null,
        // Privacy notice applicants and members agree to (Markdown); its version goes up with each change
        'privacy_notice' => null,
        'privacy_version' => 0,
        // Rejected and never-paid applications (and their documents) are deleted after this many months; 0 = kept
        'retention_months' => 24,
        // Choices on member profiles (My profile), one per line in Settings
        'profile_crops' => null,
        'profile_expertise' => null,
    ];

    /** Starting lists for profiles, until the site sets its own */
    public const DEFAULT_CROPS = [
        'Maize', 'Rice', 'Sorghum', 'Pearl millet', 'Finger millet', 'Wheat', 'Teff', 'Fonio', 'Cassava', 'Yam',
        'Sweet potato', 'Potato', 'Banana and plantain', 'Cowpea', 'Common bean', 'Groundnut', 'Soybean',
        'Bambara groundnut', 'Pigeon pea', 'Chickpea', 'Sesame', 'Cocoa', 'Coffee', 'Tea', 'Cotton', 'Oil palm',
        'Vegetables', 'Fruit trees', 'Forage crops', 'Other crops',
    ];

    public const DEFAULT_EXPERTISE = [
        'Conventional breeding', 'Molecular breeding and genomics', 'Quantitative genetics', 'Gene editing and biotechnology',
        'Phenotyping', 'Biometrics and statistics', 'Germplasm conservation', 'Seed systems', 'Plant pathology',
        'Entomology', 'Crop physiology', 'Agronomy', 'Climate adaptation', 'Nutrition and food quality',
        'Policy and regulation', 'Variety release and commercialisation', 'Training and capacity building',
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

    public const MEMBER_ID_FORMATS = [
        'running' => 'One running number, e.g. APBA0042',
        'yearly' => 'Year joined and a number, e.g. APBA-2026-0042',
    ];

    public static function memberNumberFormat(): string
    {
        $format = (string) self::get('member_number_format');

        return isset(self::MEMBER_ID_FORMATS[$format]) ? $format : 'yearly';
    }

    /** What a Member ID looks like on this site, for help texts and test emails */
    public static function exampleMemberNumber(int $number = 1): string
    {
        return self::memberNumberFormat() === 'running'
            ? sprintf('%s%04d', self::memberNumberPrefix(), $number)
            : sprintf('%s-%s-%04d', self::memberNumberPrefix(), now()->format('Y'), $number);
    }

    /** Start of every Member ID, e.g. "APBA" in APBA0042. Defaults to the site's ID. */
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

    /** The privacy notice's version; 0 while the site has none (nothing to agree to) */
    public static function privacyVersion(): int
    {
        return filled(self::get('privacy_notice')) ? max(1, (int) self::get('privacy_version')) : 0;
    }

    /** @return string[] crops members choose from on their profile */
    public static function cropOptions(): array
    {
        return self::lines(self::get('profile_crops')) ?: self::DEFAULT_CROPS;
    }

    /** @return string[] areas of expertise members choose from on their profile */
    public static function expertiseOptions(): array
    {
        return self::lines(self::get('profile_expertise')) ?: self::DEFAULT_EXPERTISE;
    }

    /** @return string[] */
    private static function lines(?string $text): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', (string) $text)))));
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
