<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Mail\MembershipMail;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipPayment;
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

    /** The receipt of a membership payment */
    public function receipt(MembershipPayment $payment): bool
    {
        if (blank($payment->email)) {
            return false;
        }

        // The PDF goes with it; the email still goes out if it cannot be made
        try {
            $pdf = app(ReceiptPdf::class);
            $files = [['data' => $pdf->render($payment), 'name' => $pdf->filename($payment), 'mime' => 'application/pdf']];
        } catch (Throwable $e) {
            report($e);
            $files = [];
        }

        return $this->send('receipt', $payment->email, $payment->name, [
            'name' => $payment->name ?: 'member',
            'receipt_number' => $payment->receipt_number,
            'member_id' => $payment->memberAccount?->member_number ?? '',
            'membership_type' => $payment->membershipType?->name ?? '',
            'amount' => $payment->formattedAmount(),
            'year' => (string) $payment->year,
        ], null, $this->receiptDetails($payment), $files);
    }

    /**
     * A renewal notice (MembershipNotice::KINDS: reminder, overdue, suspended,
     * forfeited) with a button to the portal's Payments page.
     */
    public function renewalNotice(MemberAccount $account, string $kind, \App\Plugins\Membership\Support\Standing $standing): bool
    {
        $date = fn ($d) => $d?->format('j F Y') ?? '';

        return $this->send('renewal_' . $kind, $account->email, $account->name, $this->accountValues($account) + [
            'amount' => $standing->currency . ' ' . number_format($standing->amount, 2),
            // The year asked for: the current one, or (forfeited) the last unpaid one
            'year' => (string) ($kind === 'forfeited' ? ($standing->paidYear ?? $standing->unpaidYear) : ($kind === 'reminder' ? $standing->renewYear : $standing->unpaidYear)),
            'due_date' => $date($standing->dueDate),
            'suspend_date' => $date($standing->suspendOn),
        ], ['url' => rtrim(MembershipSettings::siteUrl(), '/') . '/account/payments', 'label' => 'Renew now']);
    }

    /** To the editorial team (Settings → Member updates, else the registrations address) */
    public function publicationSubmitted(\App\Plugins\Membership\Models\MemberPublication $publication): bool
    {
        $to = MembershipSettings::get('publications_notify_email') ?: MembershipSettings::get('notification_email');
        if (blank($to)) {
            return false;
        }
        $member = $publication->memberAccount;

        return $this->send('publication_submitted', $to, null, [
            'name' => $member?->name ?? $publication->author_name ?? 'A member',
            'member_id' => $member?->member_number ?? '',
            'title' => $publication->title,
            'kind' => strtolower(\App\Plugins\Membership\Models\MemberPublication::KINDS[$publication->kind] ?? $publication->kind),
        ], ['url' => rtrim(MembershipSettings::siteUrl(), '/') . '/admin/member-updates/' . $publication->id, 'label' => 'Review it']);
    }

    /** To the member: published, or not (with the reason) */
    public function publicationReviewed(\App\Plugins\Membership\Models\MemberPublication $publication): bool
    {
        $member = $publication->memberAccount;
        if (! $member) {
            return false;
        }
        $published = $publication->status === 'approved';

        return $this->send($published ? 'publication_published' : 'publication_not_published', $member->email, $member->name, [
            'name' => $member->name,
            'title' => $publication->title,
            'kind' => strtolower(\App\Plugins\Membership\Models\MemberPublication::KINDS[$publication->kind] ?? $publication->kind),
            'reason' => filled($publication->review_note) ? 'Reason: ' . $publication->review_note : '',
        ], $published
            ? ['url' => rtrim(MembershipSettings::siteUrl(), '/') . '/', 'label' => 'Visit the website']
            : ['url' => rtrim(MembershipSettings::siteUrl(), '/') . '/account/submissions', 'label' => 'Open my submissions']);
    }

    /** Rows of a receipt's {receipt_details} box */
    public function receiptDetails(MembershipPayment $payment): array
    {
        return [
            'Receipt number' => $payment->receipt_number,
            'Member ID' => $payment->memberAccount?->member_number,
            'Name' => $payment->name,
            'Membership' => $payment->membershipType?->name,
            'Paid for' => MembershipPayment::PURPOSES[$payment->purpose] . ', ' . $payment->year,
            'Amount paid' => $payment->formattedAmount(),
            'Date paid' => $payment->paid_at?->format('j F Y'),
            'Payment method' => MembershipPayment::METHODS[$payment->method] ?? $payment->method,
            'Payment reference' => $payment->reference,
        ];
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
            'member_id' => MembershipSettings::exampleMemberNumber(42),
            'email' => 'ama.mensah@example.org',
            'reason' => 'Reason: the supporting documents were not included.',
            'receipt_number' => 'RCT-' . now()->format('Y') . '-0042',
            'year' => now()->format('Y'),
            'due_date' => Renewals::dueDate((int) now()->format('Y'))->format('j F Y'),
            'suspend_date' => Renewals::dueDate((int) now()->format('Y'))->addMonthsNoOverflow((int) MembershipSettings::get('renewal_suspend_months'))->format('j F Y'),
        ];
        $values += ['title' => 'Drought-tolerant cassava varieties for West Africa', 'kind' => 'publication'];
        $renewal = str_starts_with($kind, 'renewal_');
        $withLink = $renewal || in_array($kind, ['approved', 'welcome', 'reset'], true);
        $button = $withLink ? [
            'url' => request()->getSchemeAndHttpHost() . ($renewal ? '/account/payments' : '/account/set-password'),
            'label' => $renewal ? 'Renew now' : ($kind === 'reset' ? 'Choose a new password' : 'Set your password'),
        ] : null;
        $details = match ($kind) {
            'approved', 'welcome' => ['Member ID' => $values['member_id'], 'Membership' => $type, 'Sign-in email' => $values['email']],
            'receipt' => ['Receipt number' => $values['receipt_number'], 'Member ID' => $values['member_id'], 'Name' => $values['name'],
                'Membership' => $type, 'Paid for' => 'Membership (joining), ' . $values['year'], 'Amount paid' => $values['amount'],
                'Date paid' => now()->format('j F Y'), 'Payment method' => MembershipPayment::METHODS['paystack'], 'Payment reference' => $values['reference'] . '-1'],
            default => null,
        };

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

    private function send(string $kind, string $to, ?string $name, array $values, ?array $button = null, ?array $details = null, array $files = []): bool
    {
        if (! array_key_exists($kind, MembershipEmails::definitions()) || ! MembershipEmails::template($kind)['enabled']) {
            return false;
        }

        return $this->deliver($to, $name, new MembershipMail($kind, $values, $button, $details, null, $files));
    }

    /**
     * On the public website, the email goes out just after the page has been
     * sent, so the visitor (e.g. back from paying) does not wait for the mail
     * server; a failure is then only logged. In the admin (Livewire requests)
     * it is sent at once, so actions can say whether it went out; in commands
     * and jobs too, since those switch between sites.
     */
    private function deliver(string $to, ?string $name, MembershipMail $mail): bool
    {
        $send = function () use ($to, $name, $mail): bool {
            try {
                Mail::to($to, $name)->send($mail);

                return true;
            } catch (Throwable $e) {
                Log::warning('Membership email failed', ['kind' => $mail->kind, 'to' => $to, 'error' => $e->getMessage()]);

                return false;
            }
        };

        if (app()->runningInConsole() || request()->hasHeader('X-Livewire')) {
            return $send();
        }

        $tenant = tenant();
        \Illuminate\Support\defer(function () use ($send, $tenant) {
            if ($tenant && tenant()?->getTenantKey() !== $tenant->getTenantKey()) {
                tenancy()->initialize($tenant);
            }
            $send();
        });

        return true;
    }

}
