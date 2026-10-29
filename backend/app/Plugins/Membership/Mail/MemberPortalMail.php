<?php

namespace App\Plugins\Membership\Mail;

use App\Models\Branding;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Support\MembershipSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Emails to members about their portal account: "welcome" (set your password)
 * and "reset" (forgot password). Sent from the address in Membership settings.
 */
class MemberPortalMail extends Mailable
{
    public string $siteName;

    public function __construct(public string $kind, public MemberAccount $account, public string $link)
    {
        $this->siteName = trim((string) Branding::settings()->site_name) ?: (string) tenant()?->getTenantKey();
    }

    public function envelope(): Envelope
    {
        $from = MembershipSettings::mailFrom();

        return new Envelope(
            from: $from ? new Address($from[0], $from[1] ?? $this->siteName) : null,
            subject: $this->kind === 'welcome'
                ? "Welcome to {$this->siteName}: set your password"
                : "Reset your {$this->siteName} password",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.membership.portal', with: [
            'kind' => $this->kind,
            'account' => $this->account,
            'link' => $this->link,
            'siteName' => $this->siteName,
            'siteUrl' => preg_replace('#^(https?://[^/]+).*$#', '$1', $this->link),
        ]);
    }
}
