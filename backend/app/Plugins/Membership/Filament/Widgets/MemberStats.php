<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipPayment;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Renewals;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Members (Member accounts feature): how many, how many in good standing and
 * paid this year, retention from last year, and corporate memberships.
 */
class MemberStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Members';

    protected function getStats(): array
    {
        $year = now()->year;
        $currency = MembershipSettings::get('currency');
        $members = MemberAccount::count();
        $adminSuspended = MemberAccount::where('status', '!=', 'active')->count();

        $stats = [
            Stat::make('Members', number_format($members))
                ->description(number_format(MemberAccount::where('created_at', '>=', now()->startOfYear())->count()) . " joined in {$year}")
                ->icon('heroicon-o-users'),
        ];

        // Good standing: not suspended by the secretariat and (with Renewals) not suspended or forfeited for not paying
        $active = MemberAccount::where('status', 'active');
        $notPaying = Renewals::enabled() ? (clone $active)->whereIn('standing', ['suspended', 'forfeited'])->count() : 0;
        $good = (clone $active)->count() - $notPaying;
        $stats[] = Stat::make('In good standing', number_format($good))
            ->description(collect([
                $members ? round($good / $members * 100) . '% of members' : null,
                $adminSuspended ? "{$adminSuspended} suspended by the secretariat" : null,
                Renewals::enabled() ? "{$notPaying} suspended or forfeited for not paying" : null,
            ])->filter()->implode(' · '))
            ->color($good < $members ? 'warning' : 'success')
            ->icon('heroicon-o-check-badge');

        // Retention: of those who paid last year, how many have paid this year
        $paidFor = fn (int $y) => MembershipPayment::where('year', $y)->whereNotNull('member_account_id')->distinct()->pluck('member_account_id');
        $last = $paidFor($year - 1);
        $now = $paidFor($year);
        $stats[] = Stat::make("Paid for {$year}", number_format($now->count()))
            ->description($currency . ' ' . number_format((float) MembershipPayment::where('year', $year)->sum('amount'), 2) . ' received')
            ->icon('heroicon-o-banknotes');
        $stats[] = Stat::make('Retention', $last->isEmpty() ? '—' : round($last->intersect($now)->count() / $last->count() * 100) . '%')
            ->description($last->isEmpty()
                ? 'Shown once there are payments for ' . ($year - 1)
                : "{$last->intersect($now)->count()} of {$last->count()} who paid for " . ($year - 1) . " have paid for {$year}" . (now()->lt(Renewals::dueDate($year)) ? ' (before the due date)' : ''))
            ->icon('heroicon-o-arrow-path');

        // Corporate memberships: categories with included seats
        $corporate = MemberAccount::where('status', 'active')->whereNull('sponsor_account_id')
            ->whereHas('membershipType', fn ($q) => $q->where('included_seats', '>', 0))->with('membershipType')->get();
        if ($corporate->isNotEmpty()) {
            $included = $corporate->sum(fn ($a) => (int) $a->membershipType->included_seats);
            $used = MemberAccount::whereIn('sponsor_account_id', $corporate->pluck('id'))->count();
            $fees = (float) MembershipPayment::where('year', $year)->whereIn('member_account_id', $corporate->pluck('id'))->sum('amount');
            $stats[] = Stat::make('Corporate members', number_format($corporate->count()))
                ->description("{$used} of {$included} seats given · {$currency} " . number_format($fees, 2) . " for {$year}")
                ->icon('heroicon-o-building-office-2');
        }

        return $stats;
    }
}
