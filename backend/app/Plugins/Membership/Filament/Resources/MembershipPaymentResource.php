<?php

namespace App\Plugins\Membership\Filament\Resources;

use App\Admin\Concerns\InFunctionalArea;
use App\Plugins\Membership\Filament\Resources\MembershipPaymentResource\Pages;
use App\Plugins\Membership\Models\MembershipPayment;
use App\Plugins\Membership\Models\MembershipType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Accounts (Membership): the ledger of membership payments (joining fees and
 * renewals) with their receipts, and (Renewals) the outstanding fees, as two
 * tabs of one menu item: for the treasurer and the auditors, with filters,
 * totals and CSV exports. Read-only: payments are recorded by Paystack/Stanbic,
 * "Mark as paid" and "Record payment".
 */
class MembershipPaymentResource extends Resource
{
    use InFunctionalArea;

    protected static string $area = 'membership';

    protected static ?string $plugin = 'membership';

    protected static ?string $model = MembershipPayment::class;

    protected static ?string $slug = 'membership-payments';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Accounts';

    protected static \Filament\Pages\SubNavigationPosition $subNavigationPosition = \Filament\Pages\SubNavigationPosition::Top;

    protected static ?string $modelLabel = 'payment';

    protected static ?int $navigationSort = 4;

    /** The Accounts page's tabs: Payments, and Outstanding fees with the Renewals feature */
    public static function accountTabs(): array
    {
        $base = static::getRouteBaseName();

        return [
            \Filament\Navigation\NavigationItem::make('Payments')
                ->icon('heroicon-o-banknotes')
                ->url(static::getUrl('index'))
                ->isActiveWhen(fn () => request()->routeIs("{$base}.index")),
            \Filament\Navigation\NavigationItem::make('Outstanding fees')
                ->icon('heroicon-o-exclamation-circle')
                ->url(static::getUrl('outstanding'))
                ->isActiveWhen(fn () => request()->routeIs("{$base}.outstanding"))
                ->visible(fn () => \App\Plugins\Membership\Support\Renewals::enabled()),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['memberAccount', 'membershipType', 'registration', 'renewal']))
            ->defaultSort('paid_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')->label('Paid on')->date('j M Y')->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')->label('Receipt')->fontFamily('mono')->searchable()
                    ->url(fn (MembershipPayment $record) => url("/api/v1/membership/admin/payments/{$record->id}/receipt")),
                Tables\Columns\TextColumn::make('memberAccount.member_number')->label('Member ID')->fontFamily('mono')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('purpose')->formatStateUsing(fn ($state) => $state === 'joining' ? 'Joining' : 'Renewal')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('year')->label('For year')->sortable(),
                Tables\Columns\TextColumn::make('membershipType.name')->label('Category')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('method')->formatStateUsing(fn ($state) => MembershipPayment::METHODS[$state] ?? $state)->toggleable(),
                Tables\Columns\TextColumn::make('reference')->fontFamily('mono')->placeholder('—')->toggleable(isToggledHiddenByDefault: true)->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(fn ($state, MembershipPayment $record) => $record->formattedAmount())
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')
                        ->formatStateUsing(fn ($state) => \App\Plugins\Membership\Support\MembershipSettings::get('currency') . ' ' . number_format((float) $state, 2))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')->label('Membership year')
                    ->options(fn () => MembershipPayment::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all()),
                Tables\Filters\Filter::make('paid_between')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Paid from'),
                        Forms\Components\DatePicker::make('until')->label('Paid until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ($data['from'] ?? null) ? 'From ' . \Carbon\Carbon::parse($data['from'])->format('j M Y') : null,
                        ($data['until'] ?? null) ? 'Until ' . \Carbon\Carbon::parse($data['until'])->format('j M Y') : null,
                    ])),
                Tables\Filters\SelectFilter::make('purpose')->options(['joining' => 'Joining', 'renewal' => 'Renewal']),
                Tables\Filters\SelectFilter::make('method')->options(MembershipPayment::METHODS),
                Tables\Filters\SelectFilter::make('membership_type_id')->label('Category')
                    ->options(fn () => MembershipType::orderBy('order')->pluck('name', 'id')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn ($livewire) => static::exportCsv($livewire->getFilteredSortedTableQuery()->with(['memberAccount', 'membershipType', 'registration', 'renewal'])->get())),
            ])
            ->actions([
                Tables\Actions\Action::make('receipt')
                    ->label('Receipt PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MembershipPayment $record) => url("/api/v1/membership/admin/payments/{$record->id}/receipt")),
            ])
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Joining fees and renewals appear here once paid.');
    }

    /** The ledger as a spreadsheet: one row per payment */
    public static function exportCsv($payments)
    {
        $filename = 'membership-payments-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt', 'Paid on', 'Member ID', 'Name', 'Email', 'Category', 'Purpose', 'Membership year', 'Amount', 'Currency', 'Method', 'Payment reference', 'Bank order', 'Application / renewal', 'Notes']);
            // Names and references must not run as a formula when the file is opened in a spreadsheet
            $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
            foreach ($payments as $p) {
                fputcsv($out, array_map($safe, [
                    $p->receipt_number, $p->paid_at?->format('Y-m-d H:i'), $p->memberAccount?->member_number, $p->name, $p->email,
                    $p->membershipType?->name, $p->purpose === 'joining' ? 'Joining' : 'Renewal', $p->year,
                    number_format((float) $p->amount, 2, '.', ''), $p->currency, MembershipPayment::METHODS[$p->method] ?? $p->method,
                    $p->reference, $p->gateway_reference, $p->registration?->reference ?? $p->renewal?->reference, $p->notes,
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipPayments::route('/'),
            'outstanding' => Pages\OutstandingFees::route('/outstanding'),
        ];
    }
}
