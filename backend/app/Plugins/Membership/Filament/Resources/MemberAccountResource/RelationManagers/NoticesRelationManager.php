<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\RelationManagers;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use App\Plugins\Membership\Models\MembershipNotice;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The renewal notices emailed to a member (Renewals): the record of formal notices.
 */
class NoticesRelationManager extends RelationManager
{
    protected static string $relationship = 'notices';

    protected static ?string $title = 'Renewal notices';

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
            ->defaultSort('sent_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sent_at')->label('Sent')->dateTime('j M Y, H:i'),
                Tables\Columns\TextColumn::make('kind')->label('Notice')->formatStateUsing(fn ($state) => MembershipNotice::KINDS[$state] ?? $state),
                Tables\Columns\TextColumn::make('year')->label('For year'),
                Tables\Columns\TextColumn::make('email')->label('To'),
                Tables\Columns\IconColumn::make('sent')->label('Delivered to the mail server')->boolean(),
            ])
            ->emptyStateHeading('No renewal notices')
            ->emptyStateDescription('Reminders, overdue, suspension and forfeiture notices sent to this member appear here.');
    }
}
