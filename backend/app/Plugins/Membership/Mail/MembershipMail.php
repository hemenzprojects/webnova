<?php

namespace App\Plugins\Membership\Mail;

use App\Models\Branding;
use App\Plugins\Membership\Support\MembershipEmails;
use App\Plugins\Membership\Support\MembershipSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * One of the Membership plugin's emails (MembershipEmails::KINDS), in the
 * site's wording, from the sender set under Membership → Emails.
 */
class MembershipMail extends Mailable
{
    public string $siteName;

    /** @var array{enabled: bool, subject: string, body: string} */
    private array $template;

    /**
     * @param  array<string, ?string>  $values  placeholder values, e.g. ['name' => 'Ama Mensah']
     * @param  array{url: string, label: string}|null  $button
     * @param  array<string, ?string>|null  $details  rows of the {member_details} box, label => value
     */
    public function __construct(
        public string $kind,
        public array $values,
        public ?array $button = null,
        public ?array $details = null,
        ?array $template = null,
    ) {
        $this->siteName = trim((string) Branding::settings()->site_name) ?: (string) tenant()?->getTenantKey();
        $this->values['site_name'] ??= $this->siteName;
        $this->template = $template ?? MembershipEmails::template($kind);
    }

    public function envelope(): Envelope
    {
        $from = MembershipSettings::mailFrom();

        return new Envelope(
            from: $from ? new Address($from[0], $from[1] ?? $this->siteName) : null,
            subject: $this->fill($this->template['subject'], false),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.membership.message', with: [
            'parts' => $this->parts(),
            'button' => $this->button,
            'details' => array_filter($this->details ?? [], 'filled'),
            'siteName' => $this->siteName,
            'siteUrl' => $this->button ? preg_replace('#^(https?://[^/]+).*$#', '$1', $this->button['url']) : MembershipSettings::siteUrl(),
        ]);
    }

    /**
     * The message split into Markdown text and the {member_details} / {button}
     * blocks, which the view draws as mail components.
     *
     * @return array<int, array{type: string, text?: string}>
     */
    private function parts(): array
    {
        $body = $this->template['body'];
        // Every email with a link keeps its button
        if ($this->button && ! str_contains($body, '{button}')) {
            $body .= "\n\n{button}";
        }

        $parts = [];
        foreach (preg_split('/(\{member_details\}|\{button\})/', $body, -1, PREG_SPLIT_DELIM_CAPTURE) as $piece) {
            if ($piece === '{button}') {
                if ($this->button) {
                    $parts[] = ['type' => 'button'];
                }
            } elseif ($piece === '{member_details}') {
                if (array_filter($this->details ?? [], 'filled')) {
                    $parts[] = ['type' => 'details'];
                }
            } elseif (trim($piece) !== '') {
                $parts[] = ['type' => 'text', 'text' => trim($this->fill($piece, true))];
            }
        }

        return $parts;
    }

    /** Placeholders replaced by their values; escaped in the message body, plain in the subject */
    private function fill(string $text, bool $escape): string
    {
        return preg_replace_callback('/\{([a-z_]+)\}/', function ($m) use ($escape) {
            if (! array_key_exists($m[1], $this->values)) {
                return $m[0];
            }
            $value = (string) $this->values[$m[1]];

            return $escape ? e($value) : $value;
        }, $text);
    }
}
