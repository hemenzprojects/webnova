<?php

namespace App\Plugins\Membership\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\FormSchema;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

/**
 * Lets each site design its own registration form: sections, fields, widths,
 * required flags and fields that only apply to certain membership types.
 */
class MembershipFormBuilder extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Registration form';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Registration form';

    protected static ?string $slug = 'membership/form';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $schema = MembershipForm::current()->schema;
        $this->form->fill([
            'sections' => $schema['sections'] ?? [],
            'submit_text' => $schema['submit_text'] ?? 'Submit',
            'show_amount' => $schema['show_amount'] ?? true,
        ]);
    }

    public function getSubheading(): ?string
    {
        return 'Add sections and fields, drag to reorder. Put the "Membership type" field on the form so applicants can choose a membership and see its fee. To show the form on your site, add the "Membership form" block to a page.';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Repeater::make('sections')
                    ->label('Sections')
                    ->addActionLabel('Add section')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Section')
                    ->minItems(1)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Section title')
                            ->placeholder('Personal Information')
                            ->maxLength(120),
                        Forms\Components\Textarea::make('description')
                            ->label('Text under the title')
                            ->rows(2)
                            ->maxLength(500),
                        $this->fieldsRepeater(),
                    ]),

                Forms\Components\Section::make('Submitting')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('submit_text')
                            ->label('Submit button text')
                            ->required()
                            ->maxLength(40),
                        Forms\Components\Toggle::make('show_amount')
                            ->label('Show the fee ("Amount") above the button')
                            ->inline(false),
                    ]),
            ])
            ->statePath('data')
            // View-only roles see the settings but cannot change them
            ->disabled(! static::canManage());
    }

    private function fieldsRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('fields')
            ->label('Fields')
            ->addActionLabel('Add field')
            ->reorderableWithButtons()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->columns(4)
            ->itemLabel(fn (array $state): ?string => trim(($state['label'] ?? 'New field') . (! empty($state['required']) ? ' *' : ''))
                . ' · ' . (FormSchema::FIELD_TYPES[$state['type'] ?? 'text'] ?? '') . ' · ' . (FormSchema::WIDTHS[$state['width'] ?? 'full'] ?? ''))
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(2)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if (blank($get('key'))) {
                            $set('key', Str::of((string) $state)->snake()->replaceMatches('/[^a-z0-9_]/', '')->limit(40, '')->toString());
                        }
                    }),
                Forms\Components\Select::make('type')
                    ->options(FormSchema::FIELD_TYPES)
                    ->default('text')
                    ->required()
                    ->native(false)
                    ->live()
                    ->columnSpan(2),
                Forms\Components\TextInput::make('key')
                    ->label('Key')
                    ->helperText('Used in exports. Avoid changing it once people have registered.')
                    ->required()
                    ->regex('/^[a-z][a-z0-9_]*$/')
                    ->validationMessages(['regex' => 'Use lowercase letters, numbers and underscores, starting with a letter.'])
                    ->maxLength(40)
                    ->columnSpan(2),
                Forms\Components\Select::make('width')
                    ->options(FormSchema::WIDTHS)
                    ->default('half')
                    ->required()
                    ->native(false),
                Forms\Components\Toggle::make('required')
                    ->default(true)
                    ->inline(false),
                Forms\Components\TagsInput::make('options')
                    ->label('Options')
                    ->placeholder('Type an option and press Enter')
                    ->helperText('The choices offered, in order.')
                    ->visible(fn (Get $get) => in_array($get('type'), FormSchema::OPTION_TYPES, true))
                    ->required(fn (Get $get) => in_array($get('type'), FormSchema::OPTION_TYPES, true))
                    ->reorderable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('placeholder')
                    ->maxLength(120)
                    ->visible(fn (Get $get) => in_array($get('type'), ['text', 'textarea', 'email', 'tel', 'number'], true))
                    ->columnSpan(2),
                Forms\Components\TextInput::make('help')
                    ->label('Help text')
                    ->maxLength(200)
                    ->columnSpan(2),
                Forms\Components\Select::make('role')
                    ->label('This field holds the applicant\'s…')
                    ->options(FormSchema::ROLES)
                    ->placeholder('Nothing special')
                    ->helperText('Used for the name and email shown in the registrations list.')
                    ->visible(fn (Get $get) => in_array($get('type'), ['text', 'email'], true))
                    ->columnSpan(2),
                Forms\Components\Toggle::make('new_row')
                    ->label('Start on a new line')
                    ->inline(false)
                    ->columnSpan(2),
                Forms\Components\CheckboxList::make('show_for_types')
                    ->label('Only for these membership types')
                    ->helperText('Leave all unticked to ask everyone.')
                    ->options(fn () => MembershipType::orderBy('order')->pluck('name', 'id'))
                    ->columns(3)
                    ->hidden(fn (Get $get) => $get('type') === 'membership_type')
                    ->columnSpanFull(),
            ]);
    }

    protected function getFormActions(): array
    {
        if (! static::canManage()) {
            return [];
        }

        return [
            Action::make('save')
                ->label('Save form')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();

        $state = $this->form->getState();

        $schema = [
            'sections' => collect($state['sections'] ?? [])->map(fn ($section) => [
                'title' => $section['title'] ?? '',
                'description' => $section['description'] ?? '',
                'fields' => collect($section['fields'] ?? [])->map(fn ($field) => [
                    'key' => $field['key'],
                    'type' => $field['type'],
                    'label' => $field['label'],
                    // A membership must always be chosen: it sets the fee
                    'required' => $field['type'] === 'membership_type' ? true : (bool) ($field['required'] ?? false),
                    'width' => $field['width'] ?? 'full',
                    'new_row' => (bool) ($field['new_row'] ?? false),
                    'placeholder' => $field['placeholder'] ?? '',
                    'help' => $field['help'] ?? '',
                    'options' => in_array($field['type'], FormSchema::OPTION_TYPES, true) ? array_values($field['options'] ?? []) : [],
                    'show_for_types' => array_map('intval', $field['show_for_types'] ?? []),
                    'role' => $field['role'] ?? null,
                ])->values()->all(),
            ])->values()->all(),
            'submit_text' => $state['submit_text'],
            'show_amount' => (bool) $state['show_amount'],
        ];

        if ($problems = FormSchema::problems($schema)) {
            Notification::make()
                ->title('The form was not saved')
                ->body(implode("\n", $problems))
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $form = MembershipForm::current();
        if ($form->schema != $schema) {
            $form->update(['schema' => $schema, 'version' => $form->version + 1]);
        }

        Notification::make()->title('Registration form saved')->success()->send();
    }
}
