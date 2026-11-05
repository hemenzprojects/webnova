<?php

namespace App\Plugins\Membership\Filament\Resources\MembershipPaymentResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use App\Plugins\Membership\Filament\Resources\MembershipPaymentResource;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Renewals;
use App\Plugins\Membership\Support\Standing;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Accounts → Outstanding fees (Renewals): the members who have not paid the
 * current year (due, overdue, suspended, forfeited), with what they owe: the
 * debt list for the treasurer. Seats and categories without a fee are not
 * listed. A tab of the Accounts page, with its permission.
 */
class OutstandingFees extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = MembershipPaymentResource::class;

    protected static ?string $title = 'Accounts';

    protected static ?string $breadcrumb = 'Outstanding fees';

    protected static string $view = 'filament.membership.outstanding-fees';

    private const OWING = [Standing::DUE, Standing::OVERDUE, Standing::SUSPENDED, Standing::FORFEITED];

    public function getSubNavigation(): array
    {
        return MembershipPaymentResource::accountTabs();
    }

    protected function getHeaderWidgets(): array
    {
        return [\App\Plugins\Membership\Filament\Widgets\SubscriptionsByYear::class];
    }

    public function mount(): void
    {
        abort_unless(Renewals::enabled(), 404);

        // The list reads the stored standing (refreshed daily); bring it up to date first on sites of a normal size
        if (MemberAccount::count() <= 2000) {
            MemberAccount::with(['membershipType', 'sponsor'])->whereNull('sponsor_account_id')->lazyById(200)->each(fn (MemberAccount $a) => Renewals::refresh($a));
        }
    }

    /** Membership years not paid up to this one, e.g. 2 when 2025 and 2026 are unpaid */
    public static function unpaidYears(MemberAccount $account): int
    {
        $last = $account->paidYear() ?? (($account->created_at?->year ?? now()->year) - 1);

        return max(0, now()->year - $last);
    }

    protected function query(): Builder
    {
        return MemberAccount::query()
            ->with(['membershipType'])
            ->whereNull('sponsor_account_id')
            ->where('status', 'active')
            ->whereIn('standing', self::OWING);
    }

    public function table(Table $table): Table
    {
        $currency = MembershipSettings::get('currency');

        return $table
            ->query($this->query())
            ->defaultSort('member_number')
            ->description(function () use ($currency) {
                $owing = $this->getFilteredTableQuery()->get();
                $due = $owing->sum(fn (MemberAccount $a) => (float) $a->membershipType?->price);

                return number_format($owing->count()) . " members have not paid " . now()->year . ": {$currency} " . number_format($due, 2) . ' in this year\'s fees.';
            })
            ->columns([
                Tables\Columns\TextColumn::make('member_number')->label('Member ID')->fontFamily('mono')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('membershipType.name')->label('Category'),
                Tables\Columns\TextColumn::make('standing')->label('Fee')->badge()
                    ->formatStateUsing(fn (?string $state) => Standing::LABELS[$state] ?? '—')
                    ->color(fn (?string $state) => match ($state) { 'due' => 'gray', 'overdue' => 'warning', default => 'danger' }),
                Tables\Columns\TextColumn::make('last_paid')->label('Last paid for')
                    ->state(fn (MemberAccount $record) => $record->paidYear() ?? 'Never'),
                Tables\Columns\TextColumn::make('unpaid_years')->label('Unpaid years')
                    ->state(fn (MemberAccount $record) => self::unpaidYears($record)),
                Tables\Columns\TextColumn::make('due_now')->label(now()->year . ' fee')->alignEnd()
                    ->state(fn (MemberAccount $record) => $currency . ' ' . number_format((float) $record->membershipType?->price, 2)),
                Tables\Columns\TextColumn::make('arrears')->label('All unpaid years')->alignEnd()->toggleable()
                    ->tooltip('Unpaid years × the current fee, for information: renewing only charges the current year.')
                    ->state(fn (MemberAccount $record) => $currency . ' ' . number_format(self::unpaidYears($record) * (float) $record->membershipType?->price, 2)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('standing')->label('Fee')
                    ->options(collect(Standing::LABELS)->only(self::OWING)->all()),
                Tables\Filters\SelectFilter::make('membership_type_id')->label('Category')
                    ->options(fn () => MembershipType::where('price', '>', 0)->orderBy('order')->pluck('name', 'id')),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => $this->exportCsv($this->getFilteredSortedTableQuery()->with(['membershipType', 'profile'])->get())),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Open')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MemberAccount $record) => MemberAccountResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Nobody owes anything')
            ->emptyStateDescription('Every member has paid ' . now()->year . ', or belongs to a category without a fee.');
    }

    private function exportCsv($accounts)
    {
        $currency = MembershipSettings::get('currency');
        $filename = 'membership-outstanding-fees-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($accounts, $currency) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Member ID', 'Name', 'Email', 'Phone', 'Country', 'Category', 'Fee standing', 'Last paid for', 'Unpaid years', now()->year . ' fee', 'All unpaid years', 'Currency', 'Suspended from']);
            $safe = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
            foreach ($accounts as $a) {
                $standing = Renewals::standing($a);
                $fee = (float) $a->membershipType?->price;
                fputcsv($out, array_map($safe, [
                    $a->member_number, $a->name, $a->email, $a->profile?->phone, $a->profile?->country, $a->membershipType?->name,
                    $standing->label(), $a->paidYear() ?? 'Never', self::unpaidYears($a),
                    number_format($fee, 2, '.', ''), number_format(self::unpaidYears($a) * $fee, 2, '.', ''), $currency,
                    $standing->suspendOn?->toDateString(),
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
