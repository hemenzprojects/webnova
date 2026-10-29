<?php

namespace App\Plugins\Membership\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Models\Page as SitePage;
use App\Plugins\Membership\MembershipPlugin;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\PluginManager;
use App\Plugins\Membership\Support\Paystack;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Membership settings';

    protected static ?string $slug = 'membership/settings';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = MembershipSettings::all();
        // Never send the secret key back to the browser
        $settings['paystack_secret_key'] = null;
        $settings['features'] = app(PluginManager::class)->enabledFeatures('membership');
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        $webhookUrl = request()->getSchemeAndHttpHost() . '/api/v1/membership/paystack/webhook';
        $hasSecret = filled(MembershipSettings::get('paystack_secret_key'));

        $features = app(PluginManager::class)->get('membership')->features();

        return $form
            ->schema([
                Forms\Components\Section::make('Features')
                    ->description('Switch parts of Membership on or off. A feature that is off disappears from the admin and the website; its data is kept.')
                    ->schema([
                        Forms\Components\CheckboxList::make('features')
                            ->hiddenLabel()
                            ->options(collect($features)->map(fn ($f) => $f['label'])->all())
                            ->descriptions(collect($features)->map(fn ($f) => $f['description'])->all())
                            ->live(),
                    ]),

                Forms\Components\Section::make('Member portal')
                    ->description('Members sign in at /account. Their account is created when a registration is approved and nothing is owed, and they set their password from the welcome email.')
                    ->visible(fn (Get $get) => in_array(MembershipPlugin::FEATURE_ACCOUNTS, $get('features') ?? [], true))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('member_number_prefix')
                            ->label('Member ID prefix')
                            ->placeholder(MembershipSettings::memberNumberPrefix())
                            ->helperText('Letters and numbers only. Changing it only affects new members.')
                            ->regex('/^[A-Za-z0-9]{1,12}$/')
                            ->maxLength(12)
                            ->live(onBlur: true),
                        Forms\Components\Select::make('member_number_format')
                            ->label('Member ID format')
                            ->options(MembershipSettings::MEMBER_ID_FORMATS)
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(function (Get $get) {
                                $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $get('member_number_prefix'))) ?: MembershipSettings::memberNumberPrefix();

                                return 'The next new member gets an ID like ' . ($get('member_number_format') === 'running'
                                    ? sprintf('%s%04d', $prefix, 1)
                                    : sprintf('%s-%s-%04d', $prefix, now()->format('Y'), 1))
                                    . ', continuing after the highest existing one (including imported members).';
                            }),
                        Forms\Components\Placeholder::make('members_only_pages')
                            ->label('Members-only pages')
                            ->content(function () {
                                $pages = SitePage::where('visibility', SitePage::VISIBILITY_MEMBERS)->orderBy('title')->pluck('title');

                                return $pages->isEmpty()
                                    ? 'None yet. Choose "Members only" under Who can see this page when editing a page (Content → Pages).'
                                    : $pages->implode(', ');
                            }),
                    ]),

                Forms\Components\Section::make('Privacy and data')
                    ->description('Data protection: applicants agree to this notice on the application form, and members when they first sign in. Say what you collect, why, who sees it and how long you keep it.')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('privacy_notice')
                            ->label('Privacy notice')
                            ->toolbarButtons(['bold', 'italic', 'link', 'heading', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->helperText(fn () => MembershipSettings::privacyVersion()
                                ? 'Version ' . MembershipSettings::privacyVersion() . '. Shown at /membership/privacy. Leave empty for no consent step.'
                                : 'Empty: applicants and members are not asked to agree to anything.'),
                        Forms\Components\Toggle::make('privacy_ask_again')
                            ->label('Ask everyone to agree again')
                            ->helperText('For real changes to how data is used. Leave off for small corrections such as typos.')
                            ->default(false)
                            ->dehydrated()
                            ->visible(fn () => MembershipSettings::privacyVersion() > 0),
                        Forms\Components\TextInput::make('retention_months')
                            ->label('Delete rejected and never-paid applications after')
                            ->numeric()->minValue(0)->maxValue(120)
                            ->suffix('months')
                            ->helperText('With their documents. Applications of members are kept. 0 keeps them all.'),
                    ]),

                Forms\Components\Section::make('Member profiles')
                    ->description('The choices members tick on their profile (My profile). One per line. Renaming one removes it from profiles that had it.')
                    ->visible(fn (Get $get) => in_array(MembershipPlugin::FEATURE_ACCOUNTS, $get('features') ?? [], true))
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('profile_crops')
                            ->label('Crops')
                            ->rows(12)
                            ->placeholder(implode("\n", MembershipSettings::DEFAULT_CROPS)),
                        Forms\Components\Textarea::make('profile_expertise')
                            ->label('Areas of expertise')
                            ->rows(12)
                            ->placeholder(implode("\n", MembershipSettings::DEFAULT_EXPERTISE)),
                    ]),

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

        $wanted = $data['features'] ?? [];
        unset($data['features']);

        // A new notice, or "ask again": everyone agrees to the new version
        $askAgain = (bool) ($data['privacy_ask_again'] ?? false);
        unset($data['privacy_ask_again']);
        $oldNotice = trim((string) MembershipSettings::get('privacy_notice'));
        $newNotice = trim((string) ($data['privacy_notice'] ?? ''));
        $data['privacy_notice'] = $newNotice !== '' ? $newNotice : null;
        if ($newNotice !== '' && ($oldNotice === '' || ($askAgain && $newNotice !== $oldNotice))) {
            $data['privacy_version'] = max(1, (int) MembershipSettings::get('privacy_version')) + ($oldNotice === '' && ! MembershipSettings::get('privacy_version') ? 0 : 1);
        }
        $on = app(PluginManager::class)->setFeatures('membership', $wanted);

        MembershipSettings::save($data);

        $added = array_diff($on, $wanted);
        Notification::make()->title('Membership settings saved')
            ->body($added ? 'Also switched on, because other features need it: ' . implode(', ', array_map(fn ($f) => app(PluginManager::class)->get('membership')->features()[$f]['label'], $added)) . '.' : null)
            ->success()->send();
        $this->redirect(static::getUrl());
    }
}
