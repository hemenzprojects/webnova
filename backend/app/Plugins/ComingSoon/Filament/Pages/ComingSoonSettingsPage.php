<?php

namespace App\Plugins\ComingSoon\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Filament\Resources\PageResource;
use App\Plugins\ComingSoon\Support\ComingSoonSettings;
use App\Support\SocialPlatforms;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ComingSoonSettingsPage extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'coming_soon';

    protected static ?string $plugin = 'coming_soon';

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Coming Soon Screen';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Coming soon screen';

    protected static ?string $slug = 'coming-soon';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function getSubheading(): ?string
    {
        return 'Visitors see this screen instead of the website while the Coming Soon plugin is on. '
            . 'You are signed in, so you still see the real site. To go live, deactivate the plugin under System Administration → Plugins.';
    }

    public function mount(): void
    {
        $this->form->fill(ComingSoonSettings::forForm());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Text')
                    ->description('What visitors read on the screen.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Radio::make('mode')
                            ->label('Show the site as')
                            ->options(ComingSoonSettings::MODES)
                            ->helperText('Under maintenance also tells search engines the site will be back, so it keeps its ranking.')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('heading')
                            ->required()
                            ->maxLength(120)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('message')
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('launch_at')
                            ->label('Launch date')
                            ->helperText('Optional. Shows a countdown.')
                            ->seconds(false)
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Look')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\FileUpload::make('background_image')
                            ->label('Background image')
                            ->helperText('Optional. Without one, the screen uses your theme\'s main colour.')
                            ->image()
                            ->disk('public')
                            ->directory(PageResource::tenantDir('coming-soon'))
                            ->maxSize(5120)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('show_logo')
                            ->label('Show the site logo'),
                    ]),

                Forms\Components\Section::make('Contact details')
                    ->description('So visitors can still reach you. Leave a field empty to hide it.')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Toggle::make('show_contact')
                            ->label('Show contact details')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('contact_heading')
                            ->label('Heading')
                            ->maxLength(80)
                            ->visible(fn (Forms\Get $get) => $get('show_contact'))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->visible(fn (Forms\Get $get) => $get('show_contact')),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(40)
                            ->visible(fn (Forms\Get $get) => $get('show_contact')),
                        Forms\Components\Textarea::make('contact_address')
                            ->label('Address')
                            ->rows(2)
                            ->maxLength(300)
                            ->visible(fn (Forms\Get $get) => $get('show_contact'))
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Social media')
                    ->description('Icons linking to your social media profiles. They start as a copy of the links under Branding.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Toggle::make('show_social')
                            ->label('Show social media icons')
                            ->live(),
                        Forms\Components\Repeater::make('social_links')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->label('Icon')
                                    ->options(SocialPlatforms::selectOptions())
                                    ->allowHtml()
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('url')
                                    ->label('Link')
                                    ->required()
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(12)
                            ->reorderable()
                            ->addActionLabel('Add social media icon')
                            ->visible(fn (Forms\Get $get) => $get('show_social')),
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
            Action::make('preview')
                ->label('Preview as a visitor')
                ->color('gray')
                ->url('/?coming-soon-preview=1', shouldOpenInNewTab: true),
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();

        ComingSoonSettings::save($this->form->getState());

        Notification::make()->title('Coming soon screen saved')->success()->send();
        $this->redirect(static::getUrl());
    }
}
