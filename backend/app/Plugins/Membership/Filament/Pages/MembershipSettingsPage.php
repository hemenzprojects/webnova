<?php

namespace App\Plugins\Membership\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Paystack;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class MembershipSettingsPage extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Membership settings';

    protected static ?string $slug = 'membership/settings';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = MembershipSettings::all();
        // Never send the secret key back to the browser
        $settings['paystack_secret_key'] = null;
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        $webhookUrl = request()->getSchemeAndHttpHost() . '/api/v1/membership/paystack/webhook';
        $hasSecret = filled(MembershipSettings::get('paystack_secret_key'));

        return $form
            ->schema([
                Forms\Components\Section::make('Registrations')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('require_approval')
                            ->label('Review each registration before it is approved')
                            ->helperText('When off, registrations are approved automatically once paid (or straight away if free).')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('notification_email')
                            ->label('Email me new registrations at')
                            ->email()
                            ->placeholder('membership@your-organisation.org'),
                        Forms\Components\Select::make('currency')
                            ->options(Paystack::CURRENCIES)
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('success_message')
                            ->label('Message shown after submitting')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Paystack payments')
                    ->description(new HtmlString(
                        'Applicants pay by card or mobile money after submitting. Copy your keys from the Paystack dashboard (Settings → API Keys &amp; Webhooks). '
                        . 'Use the test keys first, then switch to the live keys.<br>Set the <strong>Webhook URL</strong> in Paystack to: <code>' . e($webhookUrl) . '</code>'
                    ))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('paystack_public_key')
                            ->label('Public key')
                            ->placeholder('pk_test_… or pk_live_…')
                            ->regex('/^pk_(test|live)_[A-Za-z0-9]+$/'),
                        Forms\Components\TextInput::make('paystack_secret_key')
                            ->label('Secret key')
                            ->password()
                            ->revealable()
                            ->placeholder($hasSecret ? 'Saved. Leave empty to keep it.' : 'sk_test_… or sk_live_…')
                            ->regex('/^sk_(test|live)_[A-Za-z0-9]+$/'),
                        Forms\Components\Placeholder::make('paystack_status')
                            ->hiddenLabel()
                            ->content($hasSecret ? '✓ Online payment is switched on.' : 'Online payment is off until a secret key is saved; registrations are recorded as unpaid.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data')
            // View-only roles see the settings but cannot change them
            ->disabled(! static::canManage());
    }

    protected function getFormActions(): array
    {
        if (! static::canManage()) {
            return [];
        }

        return [
            Action::make('save')->label('Save changes')->submit('save'),
            Action::make('removeKeys')
                ->label('Remove Paystack keys')
                ->color('gray')
                ->visible(fn () => filled(MembershipSettings::get('paystack_secret_key')))
                ->requiresConfirmation()
                ->modalDescription('Online payment stops until new keys are saved.')
                ->action(function () {
                    MembershipSettings::save(['paystack_public_key' => null, 'paystack_secret_key' => null]);
                    Notification::make()->title('Paystack keys removed')->send();
                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();

        $data = $this->form->getState();

        // An empty secret field means "keep the saved key"
        if (blank($data['paystack_secret_key'] ?? null)) {
            unset($data['paystack_secret_key']);
        }

        MembershipSettings::save($data);

        Notification::make()->title('Membership settings saved')->success()->send();
        $this->redirect(static::getUrl());
    }
}
