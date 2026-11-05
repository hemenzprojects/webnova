<?php

namespace App\Filament\Widgets;

use App\Models\SiteSlowQuery;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Database queries that took longer than config('health.slow_query_ms').
 */
class SlowQueries extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Slow queries')
            ->description('Database queries that took '.config('health.slow_query_ms').' ms or longer, kept for '.config('health.keep_slow_queries_days').' days')
            ->query(SiteSlowQuery::query()->with('tenant'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()->tooltip(fn (SiteSlowQuery $record) => $record->created_at->toDayDateTimeString())->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')->label('Site'),
                Tables\Columns\TextColumn::make('time_ms')->label('Took')->suffix(' ms')->numeric()->sortable()
                    ->color(fn (int $state) => $state >= 1000 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('url')->label('During')->fontFamily('mono')->limit(40)->placeholder('Background task'),
                Tables\Columns\TextColumn::make('sql')->label('Query')->fontFamily('mono')->limit(90)->wrap()
                    ->tooltip(fn (SiteSlowQuery $record) => $record->sql)->copyable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')->label('Site')->relationship('tenant', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No slow queries')
            ->emptyStateDescription('Nothing has taken '.config('health.slow_query_ms').' ms or longer.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
