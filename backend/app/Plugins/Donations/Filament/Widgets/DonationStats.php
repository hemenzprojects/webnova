<?php

namespace App\Plugins\Donations\Filament\Widgets;

use App\Plugins\Donations\Support\DonationQuery;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    /** Only on the Donations dashboard, not the main one */
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $query = fn () => DonationQuery::filtered($this->filters);
        $currency = DonationQuery::currency($this->filters);
        $money = fn (float $amount) => $currency . ' ' . number_format($amount, 2);

        $received = (float) $query()->where('payment_status', 'paid')->sum('amount');
        $count = $query()->where('payment_status', 'paid')->count();
        $donors = $query()->where('payment_status', 'paid')->distinct()->count('email');
        $largest = (float) $query()->where('payment_status', 'paid')->max('amount');
        $pending = (float) $query()->where('payment_status', 'unpaid')->sum('amount');
        $pendingCount = $query()->where('payment_status', 'unpaid')->count();

        return [
            Stat::make('Donations received', $money($received))
                ->description(DonationQuery::PERIODS[$this->filters['period'] ?? '12m'] ?? '')
                ->color('success')
                ->icon('heroicon-o-banknotes'),
            Stat::make('Number of donations', number_format($count))
                ->description($count ? 'Average ' . $money($received / $count) . ' · largest ' . $money($largest) : 'None yet')
                ->icon('heroicon-o-gift'),
            Stat::make('Donors', number_format($donors))
                ->description('People who have given, counted by email')
                ->icon('heroicon-o-users'),
            Stat::make('Not yet received', $money($pending))
                ->description($pendingCount ? number_format($pendingCount) . ' started or pledged but not paid' : 'Nothing outstanding')
                ->color($pendingCount ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),
        ];
    }
}
