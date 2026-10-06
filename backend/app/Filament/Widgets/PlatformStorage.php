<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use App\Services\StorageQuota;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Central admin: what all sites use together, against what they have been
 * allocated and what the server has. On the dashboard and above the Tenants
 * list. Figures may be a few minutes old.
 */
class PlatformStorage extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return tenancy()->tenant === null;
    }

    protected function getStats(): array
    {
        $quota = app(StorageQuota::class);

        $usage = $quota->totalUsage();
        $used = $usage['files'] + $usage['database'];
        $allocated = $quota->allocatedBytes();
        $capacity = $quota->capacityBytes();
        $sites = Tenant::count();

        $stats = [
            Stat::make('Used by all sites', StorageQuota::format($used))
                ->description('Files '.StorageQuota::format($usage['files']).' · Databases '.StorageQuota::format($usage['database'])." · {$sites} ".str('site')->plural($sites))
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color($capacity && $used / $capacity * 100 >= config('quota.warn_at') ? 'warning' : 'success'),
            Stat::make('Allocated to sites', StorageQuota::format($allocated))
                ->description($allocated > 0 ? floor($used / $allocated * 100).'% of the allocation is in use' : 'No limits set')
                ->color('gray'),
        ];

        if ($capacity) {
            $over = $allocated > $capacity;

            $stats[] = Stat::make('Server storage', StorageQuota::format($capacity))
                ->description($over
                    ? StorageQuota::format($allocated - $capacity).' more allocated than the server has'
                    : StorageQuota::format($capacity - $allocated).' not yet allocated')
                ->descriptionIcon($over ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-server-stack')
                ->color($over ? 'danger' : 'gray');
        }

        return $stats;
    }
}
