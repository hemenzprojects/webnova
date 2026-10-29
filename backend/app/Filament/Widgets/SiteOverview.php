<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Filament\Resources\NewsResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\ServiceResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Site dashboard: how much content the site has, each figure linking to its list.
 * Only the types the user's role may see.
 */
class SiteOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;

    /** Resource => [label, title column, "live" column, icon]; also used by RecentChanges */
    public const TYPES = [
        PageResource::class => ['Pages', 'title', 'is_published', 'heroicon-o-document-text'],
        NewsResource::class => ['News', 'title', 'is_published', 'heroicon-o-newspaper'],
        EventResource::class => ['Events', 'title', 'is_published', 'heroicon-o-calendar-days'],
        ServiceResource::class => ['Services', 'name', 'is_active', 'heroicon-o-briefcase'],
    ];

    public static function canView(): bool
    {
        return tenancy()->initialized && collect(self::TYPES)->keys()->contains(fn ($r) => $r::canViewAny());
    }

    protected function getStats(): array
    {
        $stats = [];
        foreach (self::TYPES as $resource => [$label, , $live, $icon]) {
            if (! $resource::canViewAny()) {
                continue;
            }

            $query = $resource::getModel()::query();
            $total = (clone $query)->count();
            $hidden = $total - (clone $query)->where($live, true)->count();

            $stats[] = Stat::make($label, number_format($total))
                ->description(match (true) {
                    $total === 0 => 'None yet',
                    $hidden === 0 => 'All live',
                    default => "{$hidden} not live",
                })
                ->color($hidden ? 'warning' : 'gray')
                ->icon($icon)
                ->url($resource::getUrl());
        }

        return $stats;
    }
}
