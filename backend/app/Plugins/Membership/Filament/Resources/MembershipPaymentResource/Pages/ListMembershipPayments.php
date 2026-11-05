<?php

namespace App\Plugins\Membership\Filament\Resources\MembershipPaymentResource\Pages;

use App\Plugins\Membership\Filament\Resources\MembershipPaymentResource;
use App\Plugins\Membership\Filament\Widgets\SubscriptionsByYear;
use Filament\Resources\Pages\ListRecords;

class ListMembershipPayments extends ListRecords
{
    protected static string $resource = MembershipPaymentResource::class;

    protected static ?string $title = 'Accounts';

    protected static ?string $breadcrumb = 'Payments';

    public function getSubNavigation(): array
    {
        return MembershipPaymentResource::accountTabs();
    }

    protected function getHeaderWidgets(): array
    {
        return [SubscriptionsByYear::class];
    }
}
