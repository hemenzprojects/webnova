<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipRegistration;

/**
 * What follows when an application changes, however it changed (an admin's
 * approval, a payment confirmed by Paystack, a free membership approved
 * automatically): the member account and the email that goes with it.
 *
 * - Approved: the "accepted" email; with the member portal on and nothing
 *   owed, it carries the new Member ID and the link to set a password.
 * - Paid after being approved: the account is created then, with the
 *   "welcome" email.
 * Rejections are emailed by the reject action itself, since the reviewer
 * chooses whether to send one.
 */
class ApplicationFlow
{
    public function __construct(private MemberAccounts $accounts, private MembershipMailer $mailer) {}

    /** @param  bool  $created  this save created the application */
    public function saved(MembershipRegistration $registration, bool $created): void
    {
        $becameApproved = $registration->status === 'approved' && ($created || $registration->wasChanged('status'));

        $before = $registration->memberAccount()->exists();
        $account = $this->accounts->syncFromRegistration($registration);
        // New to this application (a returning member's existing account counts too: it gets the link if it has no password)
        $newAccount = $account && ! $before && ($account->wasRecentlyCreated || ! $account->hasPassword());

        if ($becameApproved) {
            $link = $account && ! $account->hasPassword() ? $this->accounts->invitationLink($account) : null;
            $sent = $this->mailer->applicationApproved($registration, $account, $link);

            // The "accepted" email can be switched off; a new member still needs their password link
            if (! $sent && $newAccount) {
                $this->accounts->sendWelcome($account);
            }
        } elseif ($newAccount) {
            $this->accounts->sendWelcome($account);
        }
    }
}
