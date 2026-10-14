<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Models\Branding;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class HeaderSettings extends Page implements HasForms
{
    use InFunctionalAreaPage;

    protected static string $area = 'appearance';

    use InteractsWithForms;

    public const GROUP = 'header';

    /**
     * Header designs. Rendered by frontend/components/TheNavigation.vue.
     */
    public const LAYOUTS = [
        'info_bar' => 'Contact bar with menu strip',
        'classic' => 'Simple',
        'top_bar' => 'Top bar',
    ];

    public const LAYOUT_DESCRIPTIONS = [
        'info_bar' => 'Logo with phone, email and the button on the first line; the menu sits below in a coloured strip.',
        'classic' => 'Logo on the left, menu and the button on the right, all on one line.',
        'top_bar' => 'A coloured strip on top with social icons, phone and email; logo, menu and the button below.',
    ];

    /**
     * Setting key => [default value, type]. Read by Api\HeaderController.
     */
    public const DEFAULTS = [
        'header_layout' => ['classic', 'text'],
        'header_sticky' => [true, 'boolean'],
        'header_show_site_name' => [true, 'boolean'],
        'header_show_phone' => [true, 'boolean'],
        'header_show_email' => [true, 'boolean'],
        'header_show_social' => [true, 'boolean'],
        'header_cta_enabled' => [false, 'boolean'],
        'header_cta_text' => ['', 'text'],
        'header_cta_url' => ['', 'text'],
        'header_cta_new_tab' => [false, 'boolean'],
        'header_cta2_enabled' => [false, 'boolean'],
        'header_cta2_text' => ['', 'text'],
        'header_cta2_url' => ['', 'text'],
        'header_cta2_new_tab' => [false, 'boolean'],
    ];

    protected static ?string $navigationIcon = 'heroicon-o-window';

    protected static ?string $navigationLabel = 'Header Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Header Settings';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $saved = Setting::where('group', self::GROUP)->pluck('value', 'key');

        $state = [];
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            $value = $saved->has($key) ? $saved[$key] : $default;

            $state[$key] = $type === 'boolean' ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value;
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Design')
                    ->description('Choose how the header and main menu look. The menu links themselves are managed under Customize → Menus (the menu with location "header").')
                    ->schema([
                        Forms\Components\ViewField::make('header_layout')
                            ->label('Header design')
                            ->view('filament.forms.header-layout-picker')
                            ->viewData(fn (): array => [
                                'layouts' => collect(self::LAYOUTS)
                                    ->map(fn (string $label, string $key) => ['label' => $label, 'description' => self::LAYOUT_DESCRIPTIONS[$key]])
                                    ->all(),
                                'primaryColor' => Branding::settings()->primary_color,
                                'accentColor' => Branding::settings()->accent_color,
                            ])
                            ->required()
                            ->in(array_keys(self::LAYOUTS)),
                        Forms\Components\Toggle::make('header_sticky')
                            ->label('Keep the header visible while scrolling'),
                        Forms\Components\Checkbox::make('header_show_site_name')
                            ->label('Show the site name next to the logo')
                            ->helperText('Untick this if your logo already contains the name.'),
                    ]),

                Forms\Components\Section::make('Contact details and social icons')
                    ->description('Tick what the header should show. The details themselves are entered once under Customize → Branding & Settings and are shared with the footer.')
                    ->schema([
                        Forms\Components\Checkbox::make('header_show_phone')
                            ->label('Show phone number')
                            ->helperText('Appears in the "Contact bar with menu strip" and "Top bar" designs.'),
                        Forms\Components\Checkbox::make('header_show_email')
                            ->label('Show email address')
                            ->helperText('Appears in the "Contact bar with menu strip" and "Top bar" designs.'),
                        Forms\Components\Checkbox::make('header_show_social')
                            ->label('Show social media icons')
                            ->helperText('Appears in the "Top bar" design.'),
                    ]),

                Forms\Components\Section::make('Button')
                    ->description('An optional call-to-action button, for example "Become a member".')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('header_cta_enabled')
                            ->label('Show button')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('header_cta_text')
                            ->label('Button text')
                            ->maxLength(40)
                            ->required(fn (Get $get): bool => (bool) $get('header_cta_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('header_cta_enabled')),
                        Forms\Components\TextInput::make('header_cta_url')
                            ->label('Button link')
                            ->maxLength(255)
                            ->placeholder('/contact or https://...')
                            ->required(fn (Get $get): bool => (bool) $get('header_cta_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('header_cta_enabled')),
                        Forms\Components\Toggle::make('header_cta_new_tab')
                            ->label('Open in a new tab')
                            ->visible(fn (Get $get): bool => (bool) $get('header_cta_enabled')),
                    ]),

                Forms\Components\Section::make('Second button')
                    ->description('An optional outlined button shown next to the first, for example "Sign up" beside "Login".')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('header_cta2_enabled')
                            ->label('Show second button')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('header_cta2_text')
                            ->label('Button text')
                            ->maxLength(40)
                            ->required(fn (Get $get): bool => (bool) $get('header_cta2_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('header_cta2_enabled')),
                        Forms\Components\TextInput::make('header_cta2_url')
                            ->label('Button link')
                            ->maxLength(255)
                            ->placeholder('/contact or https://...')
                            ->required(fn (Get $get): bool => (bool) $get('header_cta2_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('header_cta2_enabled')),
                        Forms\Components\Toggle::make('header_cta2_new_tab')
                            ->label('Open in a new tab')
                            ->visible(fn (Get $get): bool => (bool) $get('header_cta2_enabled')),
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
            Action::make('save')
                ->label('Save changes')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();

        $data = $this->form->getState();
        $saved = Setting::where('group', self::GROUP)->pluck('value', 'key');

        foreach (self::DEFAULTS as $key => [$default, $type]) {
            // Fields hidden while the button is off keep their last saved value
            $value = array_key_exists($key, $data) ? $data[$key] : ($saved[$key] ?? $default);

            Setting::set(
                $key,
                $type === 'boolean' ? (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0') : (string) $value,
                $type,
                self::GROUP,
            );
        }

        Notification::make()
            ->title('Header settings saved')
            ->success()
            ->send();
    }
}
