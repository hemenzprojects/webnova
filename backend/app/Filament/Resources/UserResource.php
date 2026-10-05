<?php

namespace App\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * This site's admin users and their roles.
 */
class UserResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'system';

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('role_id')
                        ->label('Role')
                        ->relationship('role', 'name')
                        ->required()
                        ->preload()
                        ->native(false)
                        ->helperText('What this person can see and change. Roles are set up under Roles.'),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required(fn (string $operation) => $operation === 'create')
                        // Leave empty when editing to keep the current password
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep the current password.' : null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('role.name')->label('Role')->badge()->placeholder('No role (cannot sign in)'),
                Tables\Columns\TextColumn::make('created_at')->label('Added')->date('j M Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')->label('Role')->relationship('role', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => ! static::canManage()),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record, Tables\Actions\DeleteAction $action) {
                        if ($message = static::removalProblem($record)) {
                            Notification::make()->title($message)->danger()->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage() && $record->getKey() !== auth()->id();
    }

    /**
     * Why this user cannot lose their admin rights, if they can't.
     */
    public static function removalProblem(User $user, ?int $newRoleId = null): ?string
    {
        if (! $user->isAdministrator()) {
            return null;
        }
        $stillAdmin = $newRoleId && Role::whereKey($newRoleId)->value('is_admin');
        if ($stillAdmin) {
            return null;
        }

        $otherAdmins = User::where('id', '!=', $user->id)
            ->whereIn('role_id', Role::where('is_admin', true)->pluck('id'))
            ->exists();

        return $otherAdmins ? null : 'The site needs at least one Administrator.';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
