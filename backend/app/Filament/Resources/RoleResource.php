<?php

namespace App\Filament\Resources;

use App\Admin\Access;
use App\Admin\Areas;
use App\Admin\Concerns\InFunctionalArea;
use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use App\Plugins\PluginManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Types of admin user in this site, and what each may view or manage.
 */
class RoleResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'system';

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roles';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('description')
                        ->maxLength(255),
                    Forms\Components\Placeholder::make('admin_note')
                        ->hiddenLabel()
                        ->content('Administrators can do everything, including managing users and roles. This cannot be limited.')
                        ->visible(fn (?Role $record) => (bool) $record?->is_admin)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Group::make(static::permissionSections())
                ->hidden(fn (?Role $record) => (bool) $record?->is_admin),
        ]);
    }

    /**
     * One section per functional area, one None / View / Manage choice per menu item.
     */
    private static function permissionSections(): array
    {
        $plugins = app(PluginManager::class);
        $sections = [];

        foreach (Areas::all() as $areaKey => $area) {
            if ($areaKey === 'platform') {
                continue;
            }

            $items = Areas::itemsOf($areaKey);
            if ($items->isEmpty()) {
                continue;
            }

            $fields = $items->map(fn ($class) => Forms\Components\Radio::make('permissions.' . $class::permissionKey())
                ->label($class::permissionLabel())
                ->options(Access::LEVELS)
                ->default('none')
                ->inline()
                ->inlineLabel())->all();

            $keys = $items->map(fn ($class) => $class::permissionKey())->all();

            $pluginOff = isset($area['plugin']) && ! $plugins->isActive($area['plugin']);

            $sections[] = Forms\Components\Section::make($area['label'])
                ->icon($area['icon'])
                ->description($pluginOff ? 'This plugin is switched off; these settings apply once it is activated.' : null)
                ->collapsible()
                ->headerActions([])
                ->schema(array_merge([
                    Forms\Components\Select::make("set_all_{$areaKey}")
                        ->label('Set the whole area to')
                        ->options(Access::LEVELS)
                        ->placeholder('Choose to change every item below')
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set) use ($keys) {
                            if ($state) {
                                foreach ($keys as $key) {
                                    $set('permissions.' . $key, $state);
                                }
                            }
                        }),
                ], $fields));
        }

        return $sections;
    }

    /**
     * Keep only "view" and "manage"; anything else means no access.
     */
    public static function cleanPermissions(array $data): array
    {
        $data['permissions'] = array_filter(
            $data['permissions'] ?? [],
            fn ($level) => in_array($level, [Access::VIEW, Access::MANAGE], true)
        );

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()
                    ->description(fn (Role $record) => $record->description),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Users'),
                Tables\Columns\IconColumn::make('is_admin')->label('Full access')->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => ! static::canManage()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (Role $record) => $record->is_admin)
                    ->before(function (Role $record, Tables\Actions\DeleteAction $action) {
                        if ($record->users()->exists()) {
                            Notification::make()->title('This role still has users')
                                ->body('Move its users to another role first.')->danger()->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canManage() && ! $record->is_admin;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
