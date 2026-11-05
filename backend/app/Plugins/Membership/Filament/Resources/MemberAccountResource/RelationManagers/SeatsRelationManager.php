<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\RelationManagers;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Support\Renewals;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * The seats a corporate member's category includes (Renewals): members whose
 * membership the corporate fee covers. The corporate member manages them in
 * the portal; admins can too.
 */
class SeatsRelationManager extends RelationManager
{
    protected static string $relationship = 'seats';

    protected static ?string $title = 'Included seats';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return MemberAccountResource::canAccessArea() && Renewals::seats($ownerRecord)['included'] > 0;
    }

    public function isReadOnly(): bool
    {
        return ! MemberAccountResource::canManage();
    }

    public function table(Table $table): Table
    {
        $seats = Renewals::seats($this->getOwnerRecord());

        return $table
            ->description("{$seats['used']} of {$seats['included']} seats in use" . ($seats['type'] ? " ({$seats['type']})" : '') . '.')
            ->columns([
                Tables\Columns\TextColumn::make('member_number')->label('Member ID')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('signed_up')->label('Signed up')->state(fn (MemberAccount $record) => $record->hasPassword())->boolean(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('addSeat')
                    ->label('Give a seat')
                    ->icon('heroicon-o-user-plus')
                    ->authorize(fn () => MemberAccountResource::canManage())
                    ->visible(fn () => Renewals::seats($this->getOwnerRecord())['used'] < Renewals::seats($this->getOwnerRecord())['included'])
                    ->modalDescription('A new member gets an account and the welcome email; an existing member stops paying for themselves.')
                    ->form([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                    ])
                    ->action(function (array $data, Tables\Actions\Action $action) {
                        try {
                            $seat = app(Renewals::class)->addSeat($this->getOwnerRecord(), $data['name'], $data['email']);
                        } catch (ValidationException $e) {
                            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                            $action->halt();
                        }
                        Notification::make()->title("Seat given to {$seat->name} ({$seat->member_number})")->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('removeSeat')
                    ->label('Free the seat')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->authorize(fn () => MemberAccountResource::canManage())
                    ->requiresConfirmation()
                    ->modalDescription('The member keeps their account, but from now on pays for their own membership.')
                    ->action(function (MemberAccount $record) {
                        app(Renewals::class)->removeSeat($this->getOwnerRecord(), $record);
                        Notification::make()->title('Seat freed')->send();
                    }),
            ])
            ->emptyStateHeading('No seats given yet')
            ->emptyStateDescription('The corporate member names the people in the member portal (Our seats), or you can here.');
    }
}
