<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Mail\MembershipMail;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipRegistration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the Membership plugin's emails (MembershipEmails) in the site's wording.
 * A mail problem never stops what triggered it: it is logged and reported as false.
 */
class MembershipMailer
{
    public function applicationReceived(MembershipRegistration $registration): bool
    {
        return $this->toApplicant('received', $registration);
    }

    /**
     * With a member account that has no password yet, the email also carries
     * the member's details and the link to set a password.
     */
    public function applicationApproved(MembershipRegistration $registration, ?MemberAccount $account = null, ?string $passwordLink = null): bool
    {
        return $this->toApplicant('approved', $registration,
            $account ? ['member_id' => $account->member_number] : [],
            $passwordLink ? ['url' => $passwordLink, 'label' => 'Set your password'] : null,
            $account ? $this->accountDetails($account) : null,
        );
    }

    public function applicationRejected(MembershipRegistration $registration): bool
    {
        return $this->toApplicant('rejected', $registration, [
            'reason' => filled($registration->decision_reason) ? 'Reason: ' . $registration->decision_reason : '',
        ]);
    }

    public function welcome(MemberAccount $account, string $link): bool
    {
        return $this->send('welcome', $account->email, $account->name, $this->accountValues($account),
            ['url' => $link, 'label' => 'Set your password'], $this->accountDetails($account));
    }

    public function passwordReset(MemberAccount $account, string $link): bool
    {
        return $this->send('reset', $account->email, $account->name, $this->accountValues($account),
            ['url' => $link, 'label' => 'Choose a new password']);
    }

    /**
     * A sample of an email with made-up values, for "Send me a test". Uses the
     * wording given (e.g. not yet saved), and is sent even if the email is switched off.
     */
    public function test(string $kind, string $to, array $template): bool
    {
        $type = \App\Plugins\Membership\Models\MembershipType::orderBy('order')->value('name') ?? 'Ordinary';
        $values = [
            'name' => 'Ama Mensah',
            'reference' => 'MEM-' . now()->format('Y') . '-SAMPLE',
            'membership_type' => $type,
            'amount' => MembershipSettings::get('currency') . ' 100.00',
            'member_id' => MembershipSettings::memberNumberPrefix() . '-' . now()->format('Y') . '-0042',
            'email' => 'ama.mensah@example.org',
            'reason' => 'Reason: the supporting documents were not included.',
        ];
        $withLink = in_array($kind, ['approved', 'welcome', 'reset'], true);
        $button = $withLink ? ['url' => request()->getSchemeAndHttpHost() . '/account/set-password', 'label' => $kind === 'reset' ? 'Choose a new password' : 'Set your password'] : null;
        $details = in_array($kind, ['approved', 'welcome'], true)
            ? ['Member ID' => $values['member_id'], 'Membership' => $type, 'Sign-in email' => $values['email']]
            : null;

        $template['subject'] = '[Test] ' . $template['subject'];

        return $this->deliver($to, null, new MembershipMail($kind, $values, $button, $details, $template + ['enabled' => true]));
    }

    private function toApplicant(string $kind, MembershipRegistration $registration, array $values = [], ?array $button = null, ?array $details = null): bool
    {
        if (blank($registration->email)) {
            return false;
        }

        return $this->send($kind, $registration->email, $registration->name, $values + [
            'name' => $registration->name ?: 'applicant',
            'reference' => $registration->reference,
            'membership_type' => $registration->membershipType?->name ?? '',
            'amount' => $registration->currency . ' ' . number_format((float) $registration->amount, 2),
        ], $button, $details);
    }

    private function accountValues(MemberAccount $account): array
    {
        return [
            'name' => $account->name,
            'member_id' => $account->member_number,
            'membership_type' => $account->membershipType?->name ?? '',
            'email' => $account->email,
        ];
    }

    /** Rows of the {member_details} box */
    private function accountDetails(MemberAccount $account): array
    {
        return [
            'Member ID' => $account->member_number,
            'Membership' => $account->membershipType?->name,
            'Sign-in email' => $account->email,
        ];
    }

    private function send(string $kind, string $to, ?string $name, array $values, ?array $button = null, ?array $details = null): bool
    {
        if (! array_key_exists($kind, MembershipEmails::definitions()) || ! MembershipEmails::template($kind)['enabled']) {
            return false;
        }

        return $this->deliver($to, $name, new MembershipMail($kind, $values, $button, $details));
    }

    private function deliver(string $to, ?string $name, MembershipMail $mail): bool
    {
        try {
            Mail::to($to, $name)->send($mail);

            return true;
        } catch (Throwable $e) {
            Log::warning('Membership email failed', ['kind' => $mail->kind, 'to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
