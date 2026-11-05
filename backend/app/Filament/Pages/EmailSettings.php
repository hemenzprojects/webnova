<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Services\PlatformMail;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Central admin only: the outgoing email server every site sends through
 * (App\Services\PlatformMail). Sites set their own sender name and address.
 */
class EmailSettings extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'platform';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Email settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Email settings';

    protected static ?string $slug = 'platform/email';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = PlatformMail::settings();
        // Never send the password back to the browser
        $settings['password'] = null;
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        $hasPassword = filled(PlatformMail::settings()['password']);

        return $form
            ->schema([
                Forms\Components\Section::make('Outgoing email')
                    ->description(new HtmlString(
                        'Every site sends its emails (membership receipts, welcome and password emails, contact form…) through this mail server. '
                        . 'Each site still shows its own sender name and address, set in its admin; with Gmail, Gmail replaces the address with the account\'s own.<br>'
                        . 'Examples: <strong>Gmail</strong> smtp.gmail.com, port 587, your Gmail address and an app password (testing only, about 500 emails a day). '
                        . '<strong>Brevo</strong> smtp-relay.brevo.com, port 587. <strong>Resend</strong> smtp.resend.com, port 465, user "resend" and an API key. '
                        . 'With a sending service, verify each client\'s domain there so emails can come from their address.'
                    ))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Send all emails through this server')
                            ->helperText(fn () => $this->currentServer())
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('host')
                            ->label('SMTP server')
                            ->placeholder('smtp.gmail.com')
                            ->regex('/^[A-Za-z0-9.-]+$/')
                            ->required(fn (Get $get) => (bool) $get('enabled')),
                        Forms\Components\Select::make('port')
                            ->options([587 => '587 (STARTTLS, recommended)', 465 => '465 (SSL)', 2525 => '2525 (STARTTLS, when 587 is blocked)', 25 => '25'])
                            ->default(587)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->placeholder('Often your email address')
                            ->maxLength(255)
                            ->autocomplete('off'),
                        Forms\Components\TextInput::make('password')
                            ->label('Password or API key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder($hasPassword ? 'Saved. Leave empty to keep it.' : 'For Gmail: an app password'),
                        Forms\Components\TextInput::make('from_address')
                            ->label('Default sender address')
                            ->email()
                            ->helperText('Used when a site has not set its own sender. With Gmail, use the Gmail address.')
                            ->required(fn (Get $get) => (bool) $get('enabled')),
                        Forms\Components\TextInput::make('from_name')
                            ->label('Default sender name')
                            ->placeholder(config('app.name'))
                            ->maxLength(100),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Save changes')->submit('save'),
            Action::make('test')
                ->label('Send test email')
                ->color('gray')
                ->icon('heroicon-o-paper-airplane')
                ->modalHeading('Send a test email')
                ->modalDescription('Sends a test email with the settings in the form, even if they are not saved yet.')
                ->form([
                    Forms\Components\TextInput::make('to')->label('Send it to')->email()->required()->default(fn () => auth()->user()?->email),
                ])
                ->action(function (array $data) {
                    $settings = $this->withSavedPassword($this->form->getRawState());
                    if (blank($settings['host']) || blank($settings['from_address'])) {
                        Notification::make()->title('Enter the SMTP server and the sender address first')->danger()->send();

                        return;
                    }
                    try {
                        PlatformMail::sendTest($settings, $data['to']);
                        Notification::make()->title("Test email sent to {$data['to']}")->body('Check the inbox, and the spam folder.')->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title('The test email could not be sent')->body($this->explain($e))->danger()->persistent()->send();
                    }
                }),
        ];
    }

    public function save(): void
    {
        $data = $this->withSavedPassword($this->form->getState());
        $data['enabled'] = (bool) $data['enabled'];
        $data['port'] = (int) $data['port'];
        foreach (['host', 'username', 'from_address', 'from_name'] as $field) {
            $data[$field] = trim((string) $data[$field]) ?: null;
        }

        // Settings every site depends on: only saved once the mail server accepts them
        if ($data['enabled']) {
            try {
                PlatformMail::checkConnection($data);
            } catch (Throwable $e) {
                Notification::make()->title('Not saved: the mail server refused the connection')->body($this->explain($e))->danger()->persistent()->send();

                return;
            }
        }

        PlatformMail::save($data);
        Notification::make()->title('Email settings saved')
            ->body($data['enabled'] ? 'The mail server accepted the login. Every site now sends through it.' : 'Sites send with the server\'s own mail settings.')
            ->success()->send();
        $this->redirect(static::getUrl());
    }

    /** An empty password field keeps the saved password */
    private function withSavedPassword(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            $data['password'] = PlatformMail::settings()['password'];
        }

        return $data + PlatformMail::settings();
    }

    private function currentServer(): string
    {
        $settings = PlatformMail::settings();
        if (PlatformMail::isActive($settings)) {
            return "Now: every site sends through {$settings['host']}.";
        }

        $mailer = (string) config('mail.default');
        $host = $mailer === 'smtp' ? ' (' . config('mail.mailers.smtp.host') . ')' : '';

        return "Off: sites send with the server's own mail settings, MAIL_MAILER={$mailer}{$host}.";
    }

    /** The mail server's answer, without the password */
    private function explain(Throwable $e): string
    {
        $message = str_replace((string) ($this->withSavedPassword($this->form->getRawState())['password'] ?? ''), '…', $e->getMessage());

        return match (true) {
            str_contains($message, '535') || str_contains(strtolower($message), 'authentication') => 'The username or password was not accepted. For Gmail, use an app password, not your normal password. Server said: ' . $message,
            str_contains(strtolower($message), 'connection could not be established') || str_contains(strtolower($message), 'timed out') => 'The server could not be reached. Check the SMTP server name and the port. Server said: ' . $message,
            default => $message,
        };
    }
}
