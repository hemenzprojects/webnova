<?php

namespace App\Plugins\Membership\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\Membership\Support\MembershipEmails;
use App\Plugins\Membership\Support\MembershipMailer;
use App\Plugins\Membership\Support\MembershipSettings;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

/**
 * The wording of the emails the Membership plugin sends (MembershipEmails),
 * and the address they come from.
 */
class MembershipEmailsPage extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Emails';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Membership emails';

    protected static ?string $slug = 'membership/emails';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $data = [
            'mail_from_address' => MembershipSettings::get('mail_from_address'),
            'mail_from_name' => MembershipSettings::get('mail_from_name'),
        ];
        foreach (array_keys(MembershipEmails::definitions()) as $kind) {
            $data['emails'][$kind] = MembershipEmails::template($kind);
        }
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        $tabs = [];
        foreach (MembershipEmails::definitions() as $kind => $definition) {
            $tabs[] = Forms\Components\Tabs\Tab::make($definition['label'])
                ->schema([
                    Forms\Components\Placeholder::make("about_{$kind}")->hiddenLabel()->content($definition['description']),
                    Forms\Components\Toggle::make("emails.{$kind}.enabled")
                        ->label('Send this email')
                        ->visible($definition['optional']),
                    Forms\Components\TextInput::make("emails.{$kind}.subject")
                        ->label('Subject')
                        ->required()
                        ->maxLength(200),
                    Forms\Components\MarkdownEditor::make("emails.{$kind}.body")
                        ->label('Message')
                        ->required()
                        ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'undo', 'redo'])
                        ->helperText(new HtmlString('Placeholders: ' . collect($definition['placeholders'])
                            ->map(fn ($p) => '<code>{' . $p . '}</code> ' . e(MembershipEmails::PLACEHOLDERS[$p]))
                            ->implode(' · ')
                            . (in_array('button', $definition['placeholders'], true) ? '. The button is always included, at the end if <code>{button}</code> is removed.' : '.'))),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make("test_{$kind}")
                            ->label('Send me a test')
                            ->icon('heroicon-o-paper-airplane')
                            ->color('gray')
                            ->action(function (Get $get) use ($kind) {
                                $to = auth()->user()?->email;
                                $sent = $to && app(MembershipMailer::class)->test($kind, $to, [
                                    'subject' => (string) $get("emails.{$kind}.subject"),
                                    'body' => (string) $get("emails.{$kind}.body"),
                                ]);
                                $sent
                                    ? Notification::make()->title("Test email sent to {$to}")->body('It uses the wording shown here, even if not saved yet.')->success()->send()
                                    : Notification::make()->title('The test email could not be sent')->body('Check the mail settings, then try again.')->danger()->send();
                            }),
                        Forms\Components\Actions\Action::make("reset_{$kind}")
                            ->label('Reset to default wording')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalDescription('The subject and message go back to the standard wording. Save to keep it.')
                            ->visible(fn () => static::canManage())
                            ->action(function (Set $set) use ($kind) {
                                $set("emails.{$kind}.subject", MembershipEmails::KINDS[$kind]['subject']);
                                $set("emails.{$kind}.body", MembershipEmails::KINDS[$kind]['body']);
                            }),
                    ]),
                ]);
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Sender')
                    ->description('All membership emails come from this address. Its domain must be set up with the mail provider, or emails may land in spam.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('Send emails from')
                            ->email()
                            ->placeholder(config('mail.from.address')),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('Sender name')
                            ->placeholder(fn () => \App\Models\Branding::settings()->site_name)
                            ->maxLength(100),
                    ]),
                Forms\Components\Tabs::make('emails')->tabs($tabs)->persistTabInQueryString(),
            ])
            ->statePath('data')
            ->disabled(! static::canManage());
    }

    protected function getFormActions(): array
    {
        return static::canManage() ? [Action::make('save')->label('Save changes')->submit('save')] : [];
    }

    public function save(): void
    {
        $this->authorizeManage();

        $data = $this->form->getState();

        MembershipSettings::save([
            'mail_from_address' => trim((string) $data['mail_from_address']) ?: null,
            'mail_from_name' => trim((string) $data['mail_from_name']) ?: null,
        ]);
        // Emails of switched-off features keep their saved wording
        MembershipEmails::save(array_replace(
            collect(MembershipEmails::KINDS)->keys()->mapWithKeys(fn ($kind) => [$kind => MembershipEmails::template($kind)])->all(),
            $data['emails'] ?? [],
        ));

        Notification::make()->title('Emails saved')->success()->send();
        $this->redirect(static::getUrl());
    }
}
