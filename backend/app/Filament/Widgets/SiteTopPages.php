<?php

namespace App\Filament\Widgets;

use App\Models\SitePageView;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Site dashboard: this site's most visited pages over the last 30 days.
 */
class SiteTopPages extends TableWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    public function table(Table $table): Table
    {
        $totals = SitePageView::query()
            ->selectRaw('min(id) as id, path, sum(views) as views')
            ->where('tenant_id', tenant('id'))
            ->where('date', '>=', today()->subDays(29))
            ->groupBy('path');

        return $table
            ->heading('Most visited pages')
            ->description('Last 30 days')
            ->query(SitePageView::query()->fromSub($totals, 'site_page_views'))
            ->columns([
                Tables\Columns\TextColumn::make('path')->label('Page')
                    ->url(fn ($record) => $record->path, shouldOpenInNewTab: true),
                Tables\Columns\TextColumn::make('views')->numeric()->alignEnd(),
            ])
            ->defaultSort('views', 'desc')
            ->paginated(false)
            ->modifyQueryUsing(fn ($query) => $query->limit(8))
            ->emptyStateHeading('No visits yet')
            ->emptyStateDescription('Visits appear here once people browse the site.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}
