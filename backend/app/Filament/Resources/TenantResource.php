<?php

namespace App\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Services\ThemeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Central admin only: client sites.
 */
class TenantResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'platform';

    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Site')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('id')
                        ->label('Site ID')
                        ->helperText('Lowercase letters, numbers and dashes. Its database is named customer_{id}.')
                        ->required()
                        ->regex('/^[a-z0-9-]+$/')
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit'),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('domain')
                        ->helperText('e.g. school.edu.gh')
                        ->required()
                        ->unique(table: 'domains', column: 'domain')
                        ->visibleOn('create')
                        ->dehydrated(false),
                ]),

            Forms\Components\Section::make('First administrator')
                ->description('The person who will manage this site. They can add more users under System Administration → Users.')
                ->columns(2)
                ->visibleOn('create')
                ->schema([
                    Forms\Components\TextInput::make('admin_email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('admin_password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->required()
                        ->dehydrated(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Site ID')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('domains.domain')->label('Domain')->badge(),
                Tables\Columns\TextColumn::make('theme_slug')
                    ->label('Theme')
                    ->formatStateUsing(fn (?string $state) => app(ThemeService::class)->get($state ?? 'default')['name'] ?? $state),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
