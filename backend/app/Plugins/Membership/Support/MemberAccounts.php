<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Mail\MemberPortalMail;
use App\Plugins\Membership\MembershipPlugin;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\PluginManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * Member portal accounts ("Member accounts" feature of the Membership plugin):
 * created when a registration is approved and nothing is owed, then the member
 * sets a password from the welcome email.
 */
class MemberAccounts
{
    public function __construct(private PluginManager $plugins) {}

    public function enabled(): bool
    {
        return $this->plugins->featureIsActive('membership', MembershipPlugin::FEATURE_ACCOUNTS);
    }

    /**
     * Give an approved, paid (or free) registration its member account, once.
     * Does nothing while the feature is off; switching it on later does not
     * create accounts for earlier approvals (admins add those from Members).
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

            return $account;
        }

        $account = $this->create([
            'name' => $registration->name ?: $email,
            'email' => $email,
            'membership_type_id' => $registration->membership_type_id,
            'membership_registration_id' => $registration->id,
        ]);
        $this->sendWelcome($account);

        return $account;
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
     * e.g. APBA-2026-0042: the site's prefix, the year joined and a running number.
     */
    public function memberNumber(MemberAccount $account): string
    {
        $prefix = MembershipSettings::memberNumberPrefix();
        $year = ($account->created_at ?? now())->format('Y');
        $last = MemberAccount::where('member_number', 'like', "{$prefix}-{$year}-%")
            ->where('id', '!=', $account->id)
            ->lockForUpdate()
            ->pluck('member_number')
            ->map(fn ($number) => (int) substr(strrchr($number, '-'), 1))
            ->max() ?? 0;

        return sprintf('%s-%s-%04d', $prefix, $year, $last + 1);
    }

    /**
     * The welcome email with a "set your password" link, or a fresh link for
     * a member who never used theirs. Returns false if the email could not be sent.
     */
    public function sendWelcome(MemberAccount $account, ?string $siteUrl = null): bool
    {
        $token = Password::broker('member_invitations')->createToken($account);
        $account->update(['invited_at' => now()]);

        return $this->send($account, new MemberPortalMail('welcome', $account, $this->link($account, $token, $siteUrl)));
    }

    /** The "forgot password" email. Members who never set a password get the welcome email again. */
    public function sendPasswordReset(MemberAccount $account, ?string $siteUrl = null): bool
    {
        if (! $account->hasPassword()) {
            return $this->sendWelcome($account, $siteUrl);
        }

        $token = Password::broker('member_accounts')->createToken($account);

        return $this->send($account, new MemberPortalMail('reset', $account, $this->link($account, $token, $siteUrl)));
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
        $base = rtrim($siteUrl ?? $this->siteUrl(), '/');

        return $base . '/account/set-password?' . http_build_query(['token' => $token, 'email' => $account->email]);
    }

    /** The site's address: the request's when there is one, else the tenant's first domain */
    private function siteUrl(): string
    {
        if (app()->runningInConsole() === false && request()->getHost()) {
            return request()->getSchemeAndHttpHost();
        }

        $domain = tenant()?->domains()->orderBy('id')->value('domain');

        return $domain ? 'https://' . $domain : config('app.url');
    }

    private function send(MemberAccount $account, MemberPortalMail $mail): bool
    {
        try {
            Mail::to($account->email, $account->name)->send($mail);

            return true;
        } catch (Throwable $e) {
            // A mail problem must not undo the approval; admins can resend from Members
            Log::warning('Member portal email failed', ['account' => $account->id, 'kind' => $mail->kind, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
