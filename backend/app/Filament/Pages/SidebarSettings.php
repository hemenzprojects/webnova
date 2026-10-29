<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SidebarSettings extends Page implements HasForms
{
    use InFunctionalAreaPage;

    protected static string $area = 'appearance';

    use InteractsWithForms;

    public const GROUP = 'sidebar';

    /**
     * Setting key => [default value, type]. The frontend falls back to the
     * same defaults (see frontend/composables/useSidebarSettings.ts).
     */
    public const DEFAULTS = [
        'sidebar_position' => ['right', 'text'],

        'sidebar_services_enabled' => [true, 'boolean'],
        'sidebar_services_title' => ['Other Services', 'text'],
        'sidebar_services_limit' => [5, 'number'],
        'sidebar_services_show_image' => [true, 'boolean'],
        'sidebar_services_order' => ['order', 'text'],

        'sidebar_news_enabled' => [true, 'boolean'],
        'sidebar_news_title' => ['Other News', 'text'],
        'sidebar_news_limit' => [5, 'number'],
        'sidebar_news_show_image' => [true, 'boolean'],
        'sidebar_news_show_date' => [true, 'boolean'],
        'sidebar_news_order' => ['latest', 'text'],

        'sidebar_events_enabled' => [true, 'boolean'],
        'sidebar_events_title' => ['Other Events', 'text'],
        'sidebar_events_limit' => [5, 'number'],
        'sidebar_events_show_image' => [true, 'boolean'],
        'sidebar_events_show_date' => [true, 'boolean'],
        'sidebar_events_order' => ['upcoming', 'text'],
    ];

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Sidebar Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Sidebar Settings';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $saved = Setting::where('group', self::GROUP)->pluck('value', 'key');

        $state = [];
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            $value = $saved->has($key) ? $saved[$key] : $default;

            $state[$key] = match ($type) {
                'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'number' => (int) $value,
                default => $value,
            };
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Layout')
                    ->description('Applies to every page that shows a sidebar.')
                    ->schema([
                        Forms\Components\Select::make('sidebar_position')
                            ->label('Sidebar position')
                            ->options([
                                'right' => 'Right of the content',
                                'left' => 'Left of the content',
                            ])
                            ->required()
                            ->native(false)
                            ->selectablePlaceholder(false),
                    ]),

                $this->sectionFor('services', 'Services', [
                    'order' => 'Admin order',
                    'name' => 'Alphabetical',
                ], hasDate: false),

                $this->sectionFor('news', 'News', [
                    'latest' => 'Newest first',
                    'featured' => 'Featured first, then newest',
                ]),

                $this->sectionFor('events', 'Events', [
                    'upcoming' => 'Upcoming events only (soonest first)',
                    'latest' => 'All events (newest first)',
                ]),
            ])
            ->statePath('data')
            // View-only roles see the settings but cannot change them
            ->disabled(! static::canManage());
    }

    protected function sectionFor(string $section, string $label, array $orderOptions, bool $hasDate = true): Forms\Components\Section
    {
        $prefix = "sidebar_{$section}_";

        return Forms\Components\Section::make("{$label} sidebar")
            ->description("Shown on each {$label} detail page. Individual items can still hide it with their own \"Show sidebar\" switch.")
            ->columns(2)
            ->schema(array_filter([
                Forms\Components\Toggle::make($prefix . 'enabled')
                    ->label('Show sidebar')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make($prefix . 'title')
                    ->label('Title')
                    ->required()
                    ->maxLength(60),
                Forms\Components\TextInput::make($prefix . 'limit')
                    ->label('Number of items')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(20)
                    ->required(),
                Forms\Components\Select::make($prefix . 'order')
                    ->label('Order')
                    ->options($orderOptions)
                    ->required()
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make($prefix . 'show_image')
                    ->label('Show thumbnails'),
                $hasDate
                    ? Forms\Components\Toggle::make($prefix . 'show_date')->label('Show date')
                    : null,
            ]));
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

        foreach (self::DEFAULTS as $key => [$default, $type]) {
            $value = $data[$key] ?? $default;

            Setting::set(
                $key,
                $type === 'boolean' ? ($value ? '1' : '0') : (string) $value,
                $type,
                self::GROUP,
            );
        }

        Notification::make()
            ->title('Sidebar settings saved')
            ->success()
            ->send();
    }
}
