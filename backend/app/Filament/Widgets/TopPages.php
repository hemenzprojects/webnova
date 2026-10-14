<?php

namespace App\Filament\Widgets;

use App\Models\SitePageView;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Most visited pages across all sites over the last 7 days.
 */
class TopPages extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $totals = SitePageView::query()
            ->selectRaw('min(id) as id, tenant_id, path, sum(views) as views')
            ->where('date', '>=', today()->subDays(6))
            ->groupBy('tenant_id', 'path');

        return $table
            ->heading('Most visited pages')
            ->description('Last 7 days')
            // As a subquery, so the table can sort and filter the totals like plain columns
            ->query(SitePageView::query()->fromSub($totals, 'site_page_views')->with('tenant'))
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')->label('Site'),
                Tables\Columns\TextColumn::make('path')->label('Page')->fontFamily('mono')->searchable(),
                Tables\Columns\TextColumn::make('views')->numeric()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')->label('Site')->relationship('tenant', 'name'),
            ])
            ->defaultSort('views', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No visits recorded yet')
            ->emptyStateDescription('Page views appear here once people visit the sites.');
    }
}
