<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Plugins\Membership\Filament\Resources\MembershipTypeResource\Pages;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\MembershipSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MembershipTypeResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $model = MembershipType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Membership types';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->helperText('Shown in the form\'s membership dropdown, e.g. "Professional".')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('price')
                        ->label('Fee')
                        ->numeric()
                        ->minValue(0)
                        ->prefix(fn () => MembershipSettings::get('currency'))
                        ->helperText('0 for free membership.')
                        ->required(),
                    Forms\Components\Select::make('period')
                        ->options(MembershipType::PERIODS)
                        ->required()
                        ->default('year')
                        ->native(false),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Offered on the application form')
                        ->helperText('Off for categories the committee grants, such as Honorary or Fellow: applicants cannot choose them, but admins can give them to members under Members.')
                        ->default(true),
                    Forms\Components\TextInput::make('order')->numeric()->default(0),
                ]),
            // Renewals: e.g. Corporate includes 2 Ordinary seats for people the corporate member names
            Forms\Components\Section::make('Included seats')
                ->description('For corporate memberships: the member names people in the member portal, who become members of the chosen category at no cost, covered by this membership\'s fee.')
                ->visible(fn () => \App\Plugins\Membership\Support\Renewals::enabled())
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('included_seats')
                        ->label('Seats included')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->default(0)
                        ->helperText('0 for none.')
                        ->live(onBlur: true),
                    Forms\Components\Select::make('seat_type_id')
                        ->label('Seats are')
                        ->options(fn (?MembershipType $record) => MembershipType::where('id', '!=', $record?->id)->orderBy('order')->pluck('name', 'id'))
                        ->placeholder('e.g. Ordinary')
                        ->native(false)
                        ->required(fn (Forms\Get $get) => (int) $get('included_seats') > 0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Fee')
                    ->formatStateUsing(fn ($state, MembershipType $record) => MembershipSettings::get('currency') . ' ' . number_format((float) $state, 2) . ' ' . (MembershipType::PERIODS[$record->period] ?? '')),
                Tables\Columns\TextColumn::make('included_seats')->label('Seats')
                    ->formatStateUsing(fn ($state, MembershipType $record) => $state ? "{$state} × " . ($record->seatType?->name ?? '?') : '—')
                    ->visible(fn () => \App\Plugins\Membership\Support\Renewals::enabled()),
                Tables\Columns\TextColumn::make('registrations_count')->counts('registrations')->label('Registrations'),
                Tables\Columns\IconColumn::make('is_active')->label('On the form')->boolean()
                    ->tooltip(fn (MembershipType $record) => $record->is_active ? 'Applicants can choose it' : 'Granted by the committee only'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipTypes::route('/'),
            'create' => Pages\CreateMembershipType::route('/create'),
            'edit' => Pages\EditMembershipType::route('/{record}/edit'),
        ];
    }
}
