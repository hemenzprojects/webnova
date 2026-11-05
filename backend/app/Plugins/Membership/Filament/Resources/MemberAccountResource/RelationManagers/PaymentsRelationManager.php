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
            ->headerActions([
                // Renewals paid by bank transfer, cash… (online ones are recorded automatically)
                Tables\Actions\Action::make('record')
                    ->label('Record payment')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn () => \App\Plugins\Membership\Support\Renewals::enabled() && MemberAccountResource::canManage())
                    ->authorize(fn () => MemberAccountResource::canManage())
                    ->modalDescription('For a renewal paid by bank transfer, cash or cheque. Online payments are recorded automatically.')
                    ->fillForm(function () {
                        $standing = \App\Plugins\Membership\Support\Renewals::standing($this->getOwnerRecord());

                        return ['year' => $standing->renewYear ?? now()->year, 'amount' => $standing->amount ?: null, 'method' => 'bank', 'paid_at' => now()->toDateString(), 'send_receipt' => true];
                    })
                    ->form([
                        \Filament\Forms\Components\Select::make('year')->label('Membership year')
                            ->options(collect(range(now()->year - 2, now()->year + 1))->mapWithKeys(fn ($y) => [$y => (string) $y])->all())
                            ->required()->native(false),
                        \Filament\Forms\Components\TextInput::make('amount')->numeric()->minValue(0.01)->required()
                            ->prefix(fn () => \App\Plugins\Membership\Support\MembershipSettings::get('currency')),
                        \Filament\Forms\Components\Select::make('method')->label('Paid by')
                            ->options(collect(MembershipPayment::METHODS)->only(['bank', 'cash', 'other'])->all())
                            ->required()->native(false),
                        \Filament\Forms\Components\DatePicker::make('paid_at')->label('Paid on')->required()->maxDate(now()),
                        \Filament\Forms\Components\TextInput::make('reference')->label('Transfer or receipt reference')->maxLength(100),
                        \Filament\Forms\Components\Textarea::make('notes')->rows(2)->maxLength(500),
                        \Filament\Forms\Components\Toggle::make('send_receipt')->label('Email the member their receipt'),
                    ])
                    ->action(function (array $data) {
                        $account = $this->getOwnerRecord();
                        $payment = app(\App\Plugins\Membership\Support\Renewals::class)->recordManual($account, $data, (bool) $data['send_receipt']);
                        \Filament\Notifications\Notification::make()->title("Payment recorded: receipt {$payment->receipt_number}")
                            ->body(\App\Plugins\Membership\Support\Renewals::standing($account->refresh())->message())->success()->send();
                    }),
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
