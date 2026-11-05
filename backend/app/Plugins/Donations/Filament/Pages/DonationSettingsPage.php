<?php

namespace App\Plugins\Donations\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\Donations\Support\DonationSettings;
use App\Plugins\Membership\Support\Paystack;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class DonationSettingsPage extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'donations';

    protected static ?string $plugin = 'donations';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Donation settings';

    protected static ?string $slug = 'donations/settings';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = DonationSettings::all();
        // Never send the secret key back to the browser
        $settings['paystack_secret_key'] = null;
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        $webhookUrl = request()->getSchemeAndHttpHost() . '/api/v1/donations/paystack/webhook';
        $hasSecret = filled(DonationSettings::get('paystack_secret_key'));
        $shared = DonationSettings::usesMembershipKeys();

        return $form
            ->schema([
                Forms\Components\Section::make('Donation form')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('currencies')
                            ->label('Currencies donors can give in')
                            ->helperText('The first one is preselected. Each must be switched on for your Paystack account.')
                            ->options(Paystack::CURRENCIES)
                            ->multiple()
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('minimum_amount')
                            ->label('Smallest donation accepted')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        Forms\Components\TagsInput::make('titles')
                            ->label('Titles to choose from')
                            ->helperText('Type a title and press Enter. Remove them all to drop the Title field from the form.')
                            ->placeholder('Add a title')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('notification_email')
                            ->label('Email me about donations at')
                            ->email()
                            ->placeholder('donations@your-organisation.org')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('success_message')
                            ->label('Message shown after a successful payment')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('pledge_message')
                            ->label('Message shown while online payment is off')
                            ->helperText('The donation is then recorded as a pledge for you to follow up.')
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Paystack payments')
                    ->description(new HtmlString(
                        'Donors pay by card or mobile money. Copy your keys from the Paystack dashboard (Settings → API Keys &amp; Webhooks). '
                        . 'Use the test keys first, then switch to the live keys.<br>Set the <strong>Webhook URL</strong> in Paystack to: <code>' . e($webhookUrl) . '</code>'
                        . '<br>Paystack allows one webhook URL per account. If Membership already has its webhook set, leave it: either address confirms both.'
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
                            ->content(match (true) {
                                $hasSecret => '✓ Online donations are switched on.',
                                $shared => '✓ Online donations are switched on, using the Paystack keys saved under Membership → Settings. Enter keys here only to use a different Paystack account.',
                                default => 'Online payment is off until a secret key is saved; donations are recorded as pledges.',
                            })
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
                ->visible(fn () => filled(DonationSettings::get('paystack_secret_key')))
                ->requiresConfirmation()
                ->modalDescription('Online donations stop until new keys are saved, unless Membership has Paystack keys of its own.')
                ->action(function () {
                    DonationSettings::save(['paystack_public_key' => null, 'paystack_secret_key' => null]);
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

        DonationSettings::save($data);

        Notification::make()->title('Donation settings saved')->success()->send();
        $this->redirect(static::getUrl());
    }
}
