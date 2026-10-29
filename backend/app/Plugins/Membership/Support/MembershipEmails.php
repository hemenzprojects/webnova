<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\MembershipPlugin;
use App\Plugins\PluginManager;

/**
 * The emails the Membership plugin sends, with their default wording. Each
 * site can change the subject and message, and switch off the optional ones,
 * under Membership → Emails; changes are stored in the plugin's settings
 * ("emails" => [kind => [enabled, subject, body]], null = default).
 *
 * Messages are Markdown with {placeholders}. Two placeholders insert a block
 * rather than text: {member_details} (Member ID, category, sign-in email) and
 * {button} (the email's link, e.g. "Set your password"). An email with a
 * link always gets its button, even if {button} was removed from the message.
 */
class MembershipEmails
{
    public const KINDS = [
        'received' => [
            'label' => 'Application received',
            'description' => 'Sent to the applicant when they submit the registration form. Only while applications are reviewed (Membership → Settings): otherwise paying makes them a member and the receipt is their first email.',
            'optional' => true,
            'feature' => null,
            'placeholders' => ['name', 'reference', 'membership_type', 'amount', 'site_name'],
            'subject' => 'We have received your application',
            'body' => "# Thank you for applying\n\nDear {name},\n\nWe have received your application for {membership_type} membership of {site_name}. Your reference is **{reference}**.\n\nWe will review your application and let you know the outcome by email.\n\nKind regards,  \n{site_name}",
        ],
        'receipt' => [
            'label' => 'Payment receipt',
            'description' => 'Sent when a membership fee is paid, online or marked as paid by an admin. It is the member\'s proof of payment, with the receipt number and their Member ID.',
            'optional' => true,
            'feature' => null,
            'placeholders' => ['name', 'receipt_number', 'member_id', 'membership_type', 'amount', 'year', 'site_name', 'receipt_details'],
            'subject' => 'Your {site_name} payment receipt {receipt_number}',
            'body' => "# Thank you for your payment\n\nDear {name},\n\nWe have received your payment of {amount} for your {site_name} membership for {year}. Your receipt is attached as a PDF: please keep it as proof of payment.\n\n{receipt_details}\n\nKind regards,  \n{site_name}",
        ],
        'approved' => [
            'label' => 'Application accepted',
            'description' => 'Sent when an application is approved. With the member portal on, it also gives the new member their Member ID and a link to set their password.',
            'optional' => true,
            'feature' => null,
            'placeholders' => ['name', 'reference', 'membership_type', 'member_id', 'site_name', 'member_details', 'button'],
            'subject' => 'Your application to {site_name} has been accepted',
            'body' => "# Welcome to {site_name}\n\nDear {name},\n\nWe are pleased to tell you that your application for {membership_type} membership has been accepted.\n\n{member_details}\n\n{button}\n\nKind regards,  \n{site_name}",
        ],
        'rejected' => [
            'label' => 'Application not accepted',
            'description' => 'Sent when an application is rejected, with the reason the reviewer gives (if any). The reviewer can choose not to send it.',
            'optional' => true,
            'feature' => null,
            'placeholders' => ['name', 'reference', 'membership_type', 'reason', 'site_name'],
            'subject' => 'Your application to {site_name}',
            'body' => "# About your application\n\nDear {name},\n\nThank you for applying for {membership_type} membership of {site_name}. After reviewing your application ({reference}), we are unable to accept it at this time.\n\n{reason}\n\nIf you have any questions, please reply to this email.\n\nKind regards,  \n{site_name}",
        ],
        'welcome' => [
            'label' => 'Member portal: welcome',
            'description' => 'Sent when a member account is created other than at approval: added by hand under Members, or paid after being approved. Gives the link to set a password (valid 14 days).',
            'optional' => false,
            'feature' => MembershipPlugin::FEATURE_ACCOUNTS,
            'placeholders' => ['name', 'member_id', 'membership_type', 'email', 'site_name', 'member_details', 'button'],
            'subject' => 'Welcome to {site_name}: set your password',
            'body' => "# Welcome to {site_name}\n\nDear {name},\n\nYour membership is active and you now have access to the member portal.\n\n{member_details}\n\nTo get started, choose your password:\n\n{button}\n\nThis link is valid for 14 days. If it has expired, use \"Forgot your password?\" on the login page.\n\nKind regards,  \n{site_name}",
        ],
        'reset' => [
            'label' => 'Member portal: password reset',
            'description' => 'Sent when a member uses "Forgot your password?" or an admin sends a reset link. The link is valid for 60 minutes.',
            'optional' => false,
            'feature' => MembershipPlugin::FEATURE_ACCOUNTS,
            'placeholders' => ['name', 'site_name', 'button'],
            'subject' => 'Reset your {site_name} password',
            'body' => "# Reset your password\n\nDear {name},\n\nWe received a request to reset the password for your {site_name} member account.\n\n{button}\n\nThis link is valid for 60 minutes. If you did not ask for this, you can ignore this email: your password stays the same.\n\nKind regards,  \n{site_name}",
        ],
    ];

    /** What each placeholder stands for, shown under the editor */
    public const PLACEHOLDERS = [
        'name' => "The applicant's or member's name",
        'reference' => 'The application reference, e.g. MEM-2026-7KQ2XW',
        'membership_type' => 'The membership category, e.g. Ordinary',
        'amount' => 'The fee, e.g. USD 100.00',
        'member_id' => 'The Member ID, e.g. APBA0042',
        'email' => "The member's sign-in email",
        'reason' => 'The reason the reviewer gave (left out if none)',
        'site_name' => 'Your site name (Branding)',
        'member_details' => 'A box with the Member ID, category and sign-in email',
        'receipt_details' => 'A box with the receipt: receipt number, Member ID, amount, date and payment reference',
        'receipt_number' => 'The receipt number, e.g. RCT-2026-0042',
        'year' => 'The membership year paid for, e.g. 2026',
        'button' => "The email's button (e.g. Set your password)",
    ];

    /**
     * The emails this site uses: those of switched-off features are left out.
     *
     * @return array<string, array>
     */
    public static function definitions(): array
    {
        $plugins = app(PluginManager::class);

        return array_filter(self::KINDS, fn ($kind) => ! $kind['feature'] || $plugins->featureIsActive('membership', $kind['feature']));
    }

    /**
     * The site's version of an email: its saved wording, or the default.
     *
     * @return array{enabled: bool, subject: string, body: string}
     */
    public static function template(string $kind): array
    {
        $default = self::KINDS[$kind];
        $saved = (MembershipSettings::get('emails') ?? [])[$kind] ?? [];

        return [
            'enabled' => ! $default['optional'] || ($saved['enabled'] ?? true),
            'subject' => filled($saved['subject'] ?? null) ? $saved['subject'] : $default['subject'],
            'body' => filled($saved['body'] ?? null) ? $saved['body'] : $default['body'],
        ];
    }

    /**
     * Save the site's wording. Text equal to the default is stored as null,
     * so later improvements to the default reach sites that never changed it.
     *
     * @param  array<string, array{enabled?: bool, subject?: ?string, body?: ?string}>  $templates
     */
    public static function save(array $templates): void
    {
        $out = [];
        foreach (self::KINDS as $kind => $default) {
            $t = $templates[$kind] ?? [];
            $same = fn (?string $value, string $field) => blank($value) || trim($value) === trim($default[$field]);
            $out[$kind] = [
                'enabled' => (bool) ($t['enabled'] ?? true),
                'subject' => $same($t['subject'] ?? null, 'subject') ? null : trim($t['subject']),
                'body' => $same($t['body'] ?? null, 'body') ? null : trim($t['body']),
            ];
        }

        MembershipSettings::save(['emails' => $out]);
    }
}
