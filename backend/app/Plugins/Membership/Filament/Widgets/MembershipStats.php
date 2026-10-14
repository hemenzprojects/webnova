<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\RegistrationQuery;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MembershipStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    /** Only on the Membership dashboard, not the main one */
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $query = fn () => RegistrationQuery::filtered($this->filters);
        $currency = MembershipSettings::get('currency');

        $total = $query()->count();
        $pending = $query()->where('status', 'pending')->count();
        $approved = $query()->where('status', 'approved')->count();
        $paid = (float) $query()->where('payment_status', 'paid')->sum('amount');
        $owed = (float) $query()->where('payment_status', 'unpaid')->where('status', '!=', 'rejected')->sum('amount');

        return [
            Stat::make('Registrations', number_format($total))
                ->description(RegistrationQuery::PERIODS[$this->filters['period'] ?? '12m'] ?? '')
                ->icon('heroicon-o-users'),
            Stat::make('Waiting for review', number_format($pending))
                ->color($pending ? 'warning' : 'gray')
                ->icon('heroicon-o-clock'),
            Stat::make('Approved', number_format($approved))
                ->color('success')
                ->icon('heroicon-o-check-badge'),
            Stat::make('Fees received', $currency . ' ' . number_format($paid, 2))
                ->description($owed > 0 ? "{$currency} " . number_format($owed, 2) . ' still unpaid' : 'Nothing outstanding')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
