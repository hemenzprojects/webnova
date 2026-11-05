<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Models\MembershipPayment;
use App\Plugins\Membership\Support\MembershipSettings;
use Filament\Widgets\Widget;

/**
 * The subscription log in short: for each membership year, how many members
 * paid and how much, split into joining fees and renewals.
 */
class SubscriptionsByYear extends Widget
{
    protected static bool $isDiscovered = false;

    protected static string $view = 'filament.membership.subscriptions-by-year';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'currency' => MembershipSettings::get('currency'),
            'years' => MembershipPayment::query()
                ->selectRaw("year,
                    count(distinct member_account_id) as members,
                    sum(case when purpose = 'joining' then 1 else 0 end) as joining,
                    sum(case when purpose = 'renewal' then 1 else 0 end) as renewals,
                    sum(amount) as total")
                ->groupBy('year')
                ->orderByDesc('year')
                ->limit(6)
                ->get(),
        ];
    }
}
