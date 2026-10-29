<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Plugins\Membership\Filament\Resources\MemberAccountResource\Pages;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\MemberAccounts;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/**
 * Members' logins to the member portal (Member accounts feature). Accounts
 * are created when a registration is approved and nothing is owed; admins
 * can also add existing members by hand.
 */
class MemberAccountResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $feature = 'accounts';

    protected static ?string $model = MemberAccount::class;

    protected static ?string $slug = 'member-accounts';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'member';

    protected static ?int $navigationSort = 3;

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
                        ->label('Sign-in email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state) => strtolower(trim((string) $state))),
                    Forms\Components\Select::make('membership_type_id')
                        ->label('Membership type')
                        ->options(fn () => MembershipType::orderBy('order')->pluck('name', 'id'))
                        ->native(false),
                    Forms\Components\Select::make('status')
                        ->options(MemberAccount::STATUSES)
                        ->default('active')
                        ->required()
                        ->native(false)
                        ->helperText('Suspended members can still sign in, but cannot open members-only pages.'),
                    Forms\Components\Placeholder::make('member_number')
                        ->label('Member ID')
                        ->content(fn (?MemberAccount $record) => $record?->member_number ?? 'Given when the member is saved')
                        ->hiddenOn('create'),
                    Forms\Components\Placeholder::make('password_state')
                        ->label('Password')
                        ->content(fn (?MemberAccount $record) => $record?->hasPassword()
                            ? 'Set by the member'
                            : ($record?->invited_at ? 'Not set yet. Welcome email sent ' . $record->invited_at->diffForHumans() . '.' : 'Not set yet.'))
                        ->hiddenOn('create'),
                    Forms\Components\Toggle::make('send_welcome')
                        ->label('Email the member a link to set their password')
                        ->default(true)
                        ->dehydrated(false)
                        ->visibleOn('create')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('member_number')->label('Member ID')->searchable()->sortable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('membershipType.name')->label('Membership')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MemberAccount::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('password')
                    ->label('Signed up')
                    ->state(fn (MemberAccount $record) => $record->hasPassword())
                    ->boolean()
                    ->tooltip(fn (MemberAccount $record) => $record->hasPassword() ? 'Has set a password' : 'Has not set a password yet'),
                Tables\Columns\TextColumn::make('last_login_at')->label('Last sign-in')->since()->placeholder('Never')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Member since')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(MemberAccount::STATUSES),
                Tables\Filters\SelectFilter::make('membership_type_id')->label('Membership type')
                    ->options(fn () => MembershipType::orderBy('order')->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make(static::accountActions(Tables\Actions\Action::class)),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('suspend')
                    ->authorize(fn () => static::canManage())
                    ->label('Suspend selected')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'suspended']))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('reactivate')
                    ->authorize(fn () => static::canManage())
                    ->label('Reactivate selected')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'active']))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * Suspend, reactivate and resend email: in the table's row menu and on the edit page.
     *
     * @param  class-string  $class  Tables\Actions\Action or Filament\Actions\Action
     */
    public static function accountActions(string $class): array
    {
        return [
            $class::make('suspend')
                ->authorize(fn () => static::canManage())
                ->label('Suspend')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(fn (MemberAccount $record) => $record->status === 'active')
                ->requiresConfirmation()
                ->modalDescription('The member can still sign in, but can no longer open members-only pages.')
                ->action(function (MemberAccount $record) {
                    $record->update(['status' => 'suspended']);
                    Notification::make()->title('Member suspended')->send();
                }),
            $class::make('reactivate')
                ->authorize(fn () => static::canManage())
                ->label('Reactivate')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (MemberAccount $record) => $record->status !== 'active')
                ->action(function (MemberAccount $record) {
                    $record->update(['status' => 'active']);
                    Notification::make()->title('Member reactivated')->success()->send();
                }),
            $class::make('sendLink')
                ->authorize(fn () => static::canManage())
                ->label(fn (MemberAccount $record) => $record->hasPassword() ? 'Send password reset email' : 'Resend welcome email')
                ->icon('heroicon-o-envelope')
                ->requiresConfirmation()
                ->modalDescription(fn (MemberAccount $record) => "Sends {$record->email} a link to choose a password. Admins never see members' passwords.")
                ->action(function (MemberAccount $record) {
                    $accounts = app(MemberAccounts::class);
                    $sent = $record->hasPassword() ? $accounts->sendPasswordReset($record) : $accounts->sendWelcome($record);
                    $sent
                        ? Notification::make()->title('Email sent to ' . $record->email)->success()->send()
                        : Notification::make()->title('The email could not be sent')->body('Check the mail settings, then try again.')->danger()->send();
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberAccounts::route('/'),
            'create' => Pages\CreateMemberAccount::route('/create'),
            'edit' => Pages\EditMemberAccount::route('/{record}/edit'),
        ];
    }
}
