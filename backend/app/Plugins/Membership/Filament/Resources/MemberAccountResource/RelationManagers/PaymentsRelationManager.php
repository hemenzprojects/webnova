<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\RelationManagers;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use App\Plugins\Membership\Models\MembershipPayment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * A member's payments and receipt numbers (Payments in the portal).
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return MemberAccountResource::canAccessArea();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('receipt_number')
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')->label('Receipt')->fontFamily('mono')->searchable(),
                Tables\Columns\TextColumn::make('year')->label('For year'),
                Tables\Columns\TextColumn::make('purpose')->formatStateUsing(fn ($state) => MembershipPayment::PURPOSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('amount')->formatStateUsing(fn ($state, MembershipPayment $record) => $record->formattedAmount()),
                Tables\Columns\TextColumn::make('method')->formatStateUsing(fn ($state) => MembershipPayment::METHODS[$state] ?? $state),
                Tables\Columns\TextColumn::make('reference')->fontFamily('mono')->placeholder('—'),
                Tables\Columns\TextColumn::make('paid_at')->label('Paid on')->dateTime('j M Y'),
            ])
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('Receipt PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MembershipPayment $record) => url("/api/v1/membership/admin/payments/{$record->id}/receipt"))
                    ->visible(fn () => \App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource::canAccessArea()),
            ])
            ->emptyStateHeading('No payments recorded')
            ->emptyStateDescription('Payments appear here when the member pays online or an application is marked as paid.');
    }
}
