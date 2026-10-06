<?php

namespace App\Filament\Widgets;

use App\Services\StorageQuota;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * A site's file storage: used against its limit. On the dashboard and above
 * the Media Library.
 */
class StorageUsage extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return tenancy()->tenant !== null;
    }

    protected function getStats(): array
    {
        $quota = app(StorageQuota::class);
        $tenant = tenancy()->tenant;

        $files = $quota->fileBytes($tenant);
        $database = $quota->databaseBytes($tenant);
        $used = $files + $database;
        $limit = $quota->limitBytes($tenant);
        $percent = $quota->percentUsed($tenant, $used);

        [$color, $note] = match (true) {
            $percent >= 100 => ['danger', 'Storage is full. New uploads are blocked until files are deleted or the limit is raised.'],
            $percent >= config('quota.warn_at') => ['warning', StorageQuota::format(max($limit - $used, 0)).' left. Uploads stop when storage is full.'],
            default => ['success', StorageQuota::format($limit - $used).' left'],
        };

        return [
            Stat::make('Storage used', StorageQuota::format($used).' of '.StorageQuota::format($limit))
                ->description("{$percent}% used. {$note}")
                ->descriptionIcon($percent >= config('quota.warn_at') ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-circle-stack')
                ->color($color),
            Stat::make('Files', StorageQuota::format($files))
                ->description('Images and documents uploaded to this site'),
            Stat::make('Database', StorageQuota::format($database))
                ->description('Pages, news, registrations and other content'),
        ];
    }
}
