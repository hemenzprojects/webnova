<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Models\Menu;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FooterSettings extends Page implements HasForms
{
    use InFunctionalAreaPage;

    protected static string $area = 'appearance';

    use InteractsWithForms;

    public const GROUP = 'footer';

    public const MAX_COLUMNS = 6;

    /**
     * Widgets a footer column can hold. Rendered by frontend/components/TheFooter.vue.
     */
    public const WIDGETS = ['heading', 'text', 'menu', 'social', 'contact', 'logo'];

    /**
     * Setting key => [default value, type]. Read by Api\FooterController.
     */
    public const DEFAULTS = [
        'footer_columns' => [[], 'json'],
        'footer_copyright' => ['', 'text'],
    ];

    protected static ?string $navigationIcon = 'heroicon-o-bars-arrow-down';

    protected static ?string $navigationLabel = 'Footer Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Footer Settings';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $saved = Setting::where('group', self::GROUP)->pluck('value', 'key');

        $state = [];
        foreach (self::DEFAULTS as $key => [$default, $type]) {
            $value = $saved->has($key) ? $saved[$key] : $default;

            $state[$key] = match ($type) {
                'json' => is_array($value) ? $value : (json_decode((string) $value, true) ?: []),
                default => $value,
            };
        }

        $this->form->fill($state);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Footer columns')
                    ->description('The footer is one row. Add a column for each section, then add widgets to each column; widgets stack from top to bottom. Drag to reorder.')
                    ->schema([
                        Forms\Components\Repeater::make('footer_columns')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Builder::make('widgets')
                                    ->hiddenLabel()
                                    ->blocks($this->widgetBlocks())
                                    ->blockNumbers(false)
                                    ->blockPickerColumns(2)
                                    ->collapsible()
                                    ->reorderable()
                                    ->addActionLabel('Add widget'),
                            ])
                            ->itemLabel(function (string $uuid, Forms\Components\Repeater $component): string {
                                $position = array_search($uuid, array_keys($component->getState() ?? []));

                                return 'Column ' . ($position === false ? '' : $position + 1);
                            })
                            ->grid(['default' => 1, 'xl' => 2])
                            ->defaultItems(0)
                            ->maxItems(self::MAX_COLUMNS)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('Add column'),
                    ]),

                Forms\Components\Section::make('Copyright line')
                    ->schema([
                        Forms\Components\TextInput::make('footer_copyright')
                            ->label('Copyright text')
                            ->maxLength(200)
                            ->placeholder('© {year} WEBNOVA. All rights reserved.')
                            ->helperText('Shown below the columns. Leave blank for the default. {year} is replaced with the current year.'),
                    ]),
            ])
            ->statePath('data')
            // View-only roles see the settings but cannot change them
            ->disabled(! static::canManage());
    }

    /**
     * The widgets offered by "Add widget" in each column
     */
    protected function widgetBlocks(): array
    {
        $heading = fn () => Forms\Components\TextInput::make('heading')
            ->label('Heading')
            ->maxLength(60);

        return [
            Forms\Components\Builder\Block::make('heading')
                ->label('Heading')
                ->icon('heroicon-o-bookmark')
                ->schema([
                    Forms\Components\TextInput::make('text')
                        ->label('Heading')
                        ->required()
                        ->maxLength(60),
                ]),

            Forms\Components\Builder\Block::make('text')
                ->label('Text')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->schema([
                    Forms\Components\RichEditor::make('text')
                        ->label('Text')
                        ->required()
                        ->maxLength(5000)
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'link',
                            'bulletList',
                            'orderedList',
                            'undo',
                            'redo',
                        ]),
                ]),

            Forms\Components\Builder\Block::make('menu')
                ->label('Menu')
                ->icon('heroicon-o-bars-3')
                ->schema([
                    Forms\Components\Select::make('menu_id')
                        ->label('Menu')
                        ->options(fn () => Menu::orderBy('name')->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->helperText('Menus are managed under Customize → Menus.'),
                    $heading()->helperText('Optional. Shown above the menu links.'),
                ]),

            Forms\Components\Builder\Block::make('social')
                ->label('Social media icons')
                ->icon('heroicon-o-share')
                ->schema([
                    $heading()->helperText('Optional. The icons come from Customize → Branding & Settings (Social Media Links).'),
                ]),

            Forms\Components\Builder\Block::make('logo')
                ->label('Logo')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\Placeholder::make('logo_help')
                        ->hiddenLabel()
                        ->content('Shows the light logo from Customize → Branding & Settings (or the main logo if no light one is set).'),
                ]),

            Forms\Components\Builder\Block::make('contact')
                ->label('Contact details')
                ->icon('heroicon-o-envelope')
                ->schema([
                    $heading()->helperText('Optional. The email, phone and address come from Branding.'),
                ]),
        ];
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
                $type === 'json' ? json_encode(array_values($value)) : (string) $value,
                $type,
                self::GROUP,
            );
        }

        Notification::make()
            ->title('Footer settings saved')
            ->success()
            ->send();
    }
}
