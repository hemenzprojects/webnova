<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\MembershipPlugin;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MemberDocument;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\PluginManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * Member portal accounts ("Member accounts" feature of the Membership plugin):
 * created when a registration is approved and nothing is owed, then the member
 * sets a password from the link in their acceptance or welcome email
 * (ApplicationFlow decides which).
 */
class MemberAccounts
{
    public function __construct(private PluginManager $plugins, private MembershipMailer $mailer) {}

    public function enabled(): bool
    {
        return $this->plugins->featureIsActive('membership', MembershipPlugin::FEATURE_ACCOUNTS);
    }

    /**
     * Give an approved, paid (or free) registration its member account, once.
     * Sends nothing: a new account has wasRecentlyCreated set, and the caller
     * sends the email with its password link. Does nothing while the feature
     * is off; switching it on later does not create accounts for earlier
     * approvals (admins add those under Members).
     */
    public function syncFromRegistration(MembershipRegistration $registration): ?MemberAccount
    {
        if (! $this->enabled()
            || $registration->status !== 'approved'
            || ! in_array($registration->payment_status, ['paid', 'not_required'], true)
            || blank($registration->email)) {
            return null;
        }

        $email = strtolower(trim($registration->email));
        $account = MemberAccount::where('membership_registration_id', $registration->id)->first()
            ?? MemberAccount::where('email', $email)->first();

        if ($account) {
            // A returning member: their newest approved registration decides their category
            $account->update([
                'membership_type_id' => $registration->membership_type_id ?? $account->membership_type_id,
                'membership_registration_id' => $registration->id,
            ]);
        } else {
            $account = $this->create([
                'name' => $registration->name ?: $email,
                'email' => $email,
                'membership_type_id' => $registration->membership_type_id,
                'membership_registration_id' => $registration->id,
            ]);
        }

        $this->importApplication($account, $registration);

        return $account;
    }

    /**
     * What the application already told us, so the member need not give it
     * again: its documents (copied to My documents), the details the form
     * asked for (to My profile, where still empty) and the privacy consent.
     * Runs once per application.
     */
    public function importApplication(MemberAccount $account, MembershipRegistration $registration): void
    {
        if (MemberDocument::where('membership_registration_id', $registration->id)->exists()) {
            return;
        }

        $answers = $registration->answers ?? [];
        $profile = $account->profile()->firstOrNew();

        foreach (FormSchema::fields($registration->form_snapshot ?? []) as $field) {
            $answer = $answers[$field['key']] ?? null;
            if (blank($answer)) {
                continue;
            }

            if (ApplicationFiles::isFile($answer)) {
                if ($copy = PrivateFiles::copy($answer['file'], "members/{$account->id}")) {
                    $account->documents()->create([
                        'category' => self::documentCategory($field['label']),
                        'title' => $field['label'],
                        'file' => $copy,
                        'name' => $answer['name'],
                        'size' => (int) ($answer['size'] ?? 0),
                        'mime' => $answer['mime'] ?? null,
                        'source' => 'application',
                        'membership_registration_id' => $registration->id,
                    ]);
                }

                continue;
            }

            $target = match (true) {
                $field['type'] === 'tel' => 'phone',
                $field['type'] === 'country' => 'country',
                (bool) preg_match('/\b(institution|organi[sz]ation|university|employer|affiliation)\b/i', $field['label']) => 'institution',
                (bool) preg_match('/\b(position|job title|designation|occupation)\b/i', $field['label']) => 'position',
                default => null,
            };
            if ($target && is_string($answer) && blank($profile->{$target})) {
                $profile->{$target} = mb_substr($answer, 0, 255);
            }
        }

        $profile->save();

        if ($registration->consent_version && (int) $account->consent_version < $registration->consent_version) {
            $account->update(['consented_at' => $registration->consented_at, 'consent_version' => $registration->consent_version]);
        }
    }

    /** A sensible category for a document from the form field's label */
    private static function documentCategory(string $label): string
    {
        return match (true) {
            (bool) preg_match('/\b(cv|résumé|resume|curriculum)\b/i', $label) => 'cv',
            (bool) preg_match('/\b(degree|diploma|transcript|enrol|enrolment|enrollment|student|academic)\b/i', $label) => 'academic',
            (bool) preg_match('/\b(licen[cs]e|professional|registration)\b/i', $label) => 'professional',
            (bool) preg_match('/\b(id|passport|identification)\b/i', $label) => 'identification',
            default => 'other',
        };
    }

    /** A new account with the next Member ID */
    public function create(array $attributes): MemberAccount
    {
        return DB::transaction(function () use ($attributes) {
            $account = MemberAccount::create($attributes + ['status' => 'active']);
            $account->update(['member_number' => $this->memberNumber($account)]);

            return $account;
        });
    }

    /**
     * The next Member ID in the site's format (MembershipSettings::MEMBER_ID_FORMATS):
     * APBA0042 (one running number) or APBA-2026-0042 (year joined and a number).
     * Imported IDs in the same format count, so new members continue after them.
     */
    public function memberNumber(MemberAccount $account): string
    {
        $prefix = MembershipSettings::memberNumberPrefix();
        $running = MembershipSettings::memberNumberFormat() === 'running';
        $start = $running ? $prefix : $prefix . '-' . ($account->created_at ?? now())->format('Y') . '-';

        $last = MemberAccount::where('member_number', 'like', str_replace(['%', '_'], ['\\%', '\\_'], $start) . '%')
            ->where('id', '!=', $account->id)
            ->lockForUpdate()
            ->pluck('member_number')
            ->map(fn ($number) => substr($number, strlen($start)))
            ->filter(fn ($rest) => ctype_digit($rest))
            ->map(fn ($rest) => (int) $rest)
            ->max() ?? 0;

        return $start . sprintf('%04d', $last + 1);
    }

    /**
     * A "set your password" link for an account without a password (valid 14 days).
     */
    public function invitationLink(MemberAccount $account, ?string $siteUrl = null): string
    {
        $token = Password::broker('member_invitations')->createToken($account);
        $account->update(['invited_at' => now()]);

        return $this->link($account, $token, $siteUrl);
    }

    /** The welcome email with a "set your password" link. False if it could not be sent. */
    public function sendWelcome(MemberAccount $account, ?string $siteUrl = null): bool
    {
        return $this->mailer->welcome($account, $this->invitationLink($account, $siteUrl));
    }

    /** The "forgot password" email. Members who never set a password get the welcome email again. */
    public function sendPasswordReset(MemberAccount $account, ?string $siteUrl = null): bool
    {
        if (! $account->hasPassword()) {
            return $this->sendWelcome($account, $siteUrl);
        }

        $token = Password::broker('member_accounts')->createToken($account);

        return $this->mailer->passwordReset($account, $this->link($account, $token, $siteUrl));
    }

    /**
     * The broker a set-password token belongs to: welcome links last longer
     * than reset links and are only for accounts without a password.
     */
    public function brokerFor(MemberAccount $account): string
    {
        return $account->hasPassword() ? 'member_accounts' : 'member_invitations';
    }

    private function link(MemberAccount $account, string $token, ?string $siteUrl): string
    {
        $base = rtrim($siteUrl ?? MembershipSettings::siteUrl(), '/');

        return $base . '/account/set-password?' . http_build_query(['token' => $token, 'email' => $account->email]);
    }
}
