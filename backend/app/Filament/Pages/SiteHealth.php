<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Filament\Widgets;
use App\Models\Tenant;
use App\Services\SiteHealth as Health;
use App\Services\StorageQuota;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Central admin only: how each site is doing. Traffic, response times,
 * errors, slow queries and storage.
 */
class SiteHealth extends Page implements HasTable
{
    use InFunctionalAreaPage;
    use InteractsWithTable;

    protected static string $area = 'platform';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.site-health';

    private const DAYS = 7;

    private ?Collection $summary = null;

    protected function getHeaderWidgets(): array
    {
        return [Widgets\PlatformStorage::class, Widgets\PageViewsChart::class, Widgets\ResponseTimeChart::class];
    }

    protected function getFooterWidgets(): array
    {
        return [Widgets\TopPages::class, Widgets\SlowQueries::class];
    }

    /** The site's figures for the period, read once per request */
    private function figures(Tenant $tenant): object
    {
        $this->summary ??= app(Health::class)->summary(self::DAYS);

        return $this->summary[$tenant->id];
    }

    private function status(Tenant $tenant): array
    {
        return app(Health::class)->status($tenant, $this->figures($tenant));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Sites')
            ->description('Last '.self::DAYS.' days')
            ->query(Tenant::query()->with('domains'))
            ->defaultSort('name')
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Site')
                    ->weight('medium')
                    ->description(fn (Tenant $record) => $record->domains->pluck('domain')->join(', ')),
                Tables\Columns\TextColumn::make('status')
                    ->state(fn (Tenant $record) => $this->status($record)['status'])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Health::HEALTHY => 'Healthy',
                        Health::ATTENTION => 'Needs attention',
                        Health::CRITICAL => 'Critical',
                        default => 'No data',
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Health::HEALTHY => 'success',
                        Health::ATTENTION => 'warning',
                        Health::CRITICAL => 'danger',
                        default => 'gray',
                    })
                    ->description(fn (Tenant $record) => implode(' · ', $this->status($record)['reasons'])),
                Tables\Columns\TextColumn::make('views')
                    ->label('Page views')
                    ->state(fn (Tenant $record) => $this->figures($record)->views)
                    ->numeric(),
                Tables\Columns\TextColumn::make('requests')
                    ->label('Server requests')
                    ->state(fn (Tenant $record) => $this->figures($record)->requests)
                    ->numeric(),
                Tables\Columns\TextColumn::make('avg_ms')
                    ->label('Avg response')
                    ->state(fn (Tenant $record) => $this->figures($record)->requests ? $this->figures($record)->avg_ms.' ms' : '—')
                    ->description(fn (Tenant $record) => $this->figures($record)->slow_requests
                        ? number_format($this->figures($record)->slow_requests).' over '.(config('health.slow_request_ms') / 1000).' s'
                        : null),
                Tables\Columns\TextColumn::make('errors')
                    ->label('Server errors')
                    ->state(fn (Tenant $record) => $this->figures($record)->errors)
                    ->numeric()
                    ->color(fn (int $state) => $state ? 'danger' : null)
                    ->description(fn (Tenant $record) => $this->figures($record)->errors ? $this->figures($record)->error_percent.'% of requests' : null),
                Tables\Columns\TextColumn::make('slow_queries')
                    ->label('Slow queries')
                    ->state(fn (Tenant $record) => $this->figures($record)->slow_queries)
                    ->numeric()
                    ->color(fn (int $state) => $state ? 'warning' : null),
                Tables\Columns\TextColumn::make('storage')
                    ->state(function (Tenant $record): string {
                        $quota = app(StorageQuota::class);

                        return StorageQuota::format($quota->cachedUsedBytes($record)).' of '.StorageQuota::format($quota->limitBytes($record));
                    }),
            ]);
    }
}
