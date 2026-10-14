<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Per-site statistics for the platform admin: page views, response times,
 * server errors and slow queries. Kept in the central database as one row per
 * site per day, so recording costs one small write per request.
 *
 * Recording must never break a site, so every write swallows its errors.
 */
class SiteHealth
{
    public const HEALTHY = 'healthy';

    public const ATTENTION = 'attention';

    public const CRITICAL = 'critical';

    public const NO_DATA = 'no-data';

    private function db(): ConnectionInterface
    {
        return DB::connection(config('tenancy.database.central_connection'));
    }

    private function enabledFor(?string $tenantId): bool
    {
        return $tenantId !== null && config('health.enabled');
    }

    // ------------------------------------------------------------ Recording

    public function recordRequest(?string $tenantId, int $ms, int $status): void
    {
        if (! $this->enabledFor($tenantId)) {
            return;
        }

        $this->quietly(fn () => $this->db()->statement(
            'insert into site_stats (tenant_id, date, requests, total_ms, slow_requests, errors) values (?, ?, 1, ?, ?, ?)
             on conflict (tenant_id, date) do update set
                requests = site_stats.requests + 1,
                total_ms = site_stats.total_ms + excluded.total_ms,
                slow_requests = site_stats.slow_requests + excluded.slow_requests,
                errors = site_stats.errors + excluded.errors',
            [$tenantId, today()->toDateString(), $ms, (int) ($ms >= config('health.slow_request_ms')), (int) ($status >= 500)],
        ));

        // Now and then, drop figures past their keep time
        if (random_int(1, 1000) === 1) {
            $this->quietly(fn () => $this->prune());
        }
    }

    public function recordPageView(?string $tenantId, string $path): void
    {
        if (! $this->enabledFor($tenantId)) {
            return;
        }

        // /news/my-story/?utm=x#top  →  /news/my-story
        $path = '/'.trim(Str::before(Str::before($path, '?'), '#'), '/');

        $this->quietly(fn () => $this->db()->statement(
            'insert into site_page_views (tenant_id, date, path, views) values (?, ?, ?, 1)
             on conflict (tenant_id, date, path) do update set views = site_page_views.views + 1',
            [$tenantId, today()->toDateString(), Str::limit($path, 250, '')],
        ));
    }

    public function recordSlowQuery(?string $tenantId, string $sql, float $ms): void
    {
        if (! $this->enabledFor($tenantId)) {
            return;
        }

        $this->quietly(fn () => $this->db()->table('site_slow_queries')->insert([
            'tenant_id' => $tenantId,
            'sql' => Str::limit($sql, 2000),
            'time_ms' => (int) round($ms),
            'url' => app()->runningInConsole() ? null : Str::limit(request()->method().' /'.ltrim(request()->path(), '/'), 500, ''),
            'created_at' => now(),
        ]));
    }

    public function prune(): void
    {
        $this->db()->table('site_stats')->where('date', '<', today()->subDays(config('health.keep_days')))->delete();
        $this->db()->table('site_page_views')->where('date', '<', today()->subDays(config('health.keep_days')))->delete();
        $this->db()->table('site_slow_queries')->where('created_at', '<', now()->subDays(config('health.keep_slow_queries_days')))->delete();
    }

    private function quietly(callable $write): void
    {
        try {
            $write();
        } catch (Throwable $e) {
            report($e);
        }
    }

    // -------------------------------------------------------------- Reading

    /**
     * Each site's totals over the last $days days (today included), by tenant id.
     *
     * @return Collection<string, object{requests: int, avg_ms: int, slow_requests: int, errors: int, error_percent: float, views: int, slow_queries: int}>
     */
    public function summary(int $days = 7): Collection
    {
        $from = today()->subDays($days - 1);

        $stats = $this->db()->table('site_stats')->where('date', '>=', $from)->groupBy('tenant_id')
            ->selectRaw('tenant_id, sum(requests) as requests, sum(total_ms) as total_ms, sum(slow_requests) as slow_requests, sum(errors) as errors')
            ->get()->keyBy('tenant_id');
        $views = $this->db()->table('site_page_views')->where('date', '>=', $from)->groupBy('tenant_id')
            ->selectRaw('tenant_id, sum(views) as views')->pluck('views', 'tenant_id');
        $slow = $this->db()->table('site_slow_queries')->where('created_at', '>=', $from)->groupBy('tenant_id')
            ->selectRaw('tenant_id, count(*) as total')->pluck('total', 'tenant_id');

        return Tenant::pluck('id')->mapWithKeys(function (string $id) use ($stats, $views, $slow) {
            $requests = (int) ($stats[$id]->requests ?? 0);
            $errors = (int) ($stats[$id]->errors ?? 0);

            return [$id => (object) [
                'requests' => $requests,
                'avg_ms' => $requests ? (int) round($stats[$id]->total_ms / $requests) : 0,
                'slow_requests' => (int) ($stats[$id]->slow_requests ?? 0),
                'errors' => $errors,
                'error_percent' => $requests ? round($errors / $requests * 100, 1) : 0.0,
                'views' => (int) ($views[$id] ?? 0),
                'slow_queries' => (int) ($slow[$id] ?? 0),
            ]];
        });
    }

    /**
     * A site's state and what, if anything, is wrong with it.
     *
     * @return array{status: string, reasons: string[]}
     */
    public function status(Tenant $tenant, object $summary): array
    {
        $critical = [];
        $attention = [];

        $storage = app(StorageQuota::class);
        $percent = $storage->percentUsed($tenant, $storage->cachedUsedBytes($tenant));

        if ($percent >= 100) {
            $critical[] = 'Storage is full';
        } elseif ($percent >= config('quota.warn_at')) {
            $attention[] = "Storage {$percent}% used";
        }

        if ($summary->error_percent >= config('health.critical.error_percent')) {
            $critical[] = "{$summary->error_percent}% of requests fail";
        } elseif ($summary->error_percent >= config('health.attention.error_percent')) {
            $attention[] = "{$summary->error_percent}% of requests fail";
        }

        if ($summary->avg_ms >= config('health.critical.avg_ms')) {
            $critical[] = 'Very slow responses';
        } elseif ($summary->avg_ms >= config('health.attention.avg_ms')) {
            $attention[] = 'Slow responses';
        }

        if ($summary->slow_queries >= config('health.attention.slow_queries')) {
            $attention[] = "{$summary->slow_queries} slow queries";
        }

        return match (true) {
            (bool) $critical => ['status' => self::CRITICAL, 'reasons' => [...$critical, ...$attention]],
            (bool) $attention => ['status' => self::ATTENTION, 'reasons' => $attention],
            $summary->requests === 0 && $summary->views === 0 => ['status' => self::NO_DATA, 'reasons' => ['Nothing recorded yet']],
            default => ['status' => self::HEALTHY, 'reasons' => []],
        };
    }

    /**
     * One value per site per day, for charts.
     *
     * @param  'views'|'avg_ms'  $metric
     * @return array{labels: string[], series: array<string, array<int, int|null>>}  series keyed by site name
     */
    public function daily(string $metric, int $days): array
    {
        $from = today()->subDays($days - 1);

        $rows = $metric === 'views'
            ? $this->db()->table('site_page_views')->where('date', '>=', $from)->groupBy('tenant_id', 'date')
                ->selectRaw('tenant_id, date, sum(views) as value')->get()
            : $this->db()->table('site_stats')->where('date', '>=', $from)->where('requests', '>', 0)
                ->selectRaw('tenant_id, date, round(total_ms::numeric / requests) as value')->get();

        $values = $rows->groupBy('tenant_id')->map(fn ($rows) => $rows->pluck('value', 'date'));
        $dates = collect(range(0, $days - 1))->map(fn (int $i) => $from->copy()->addDays($i));

        $series = [];
        foreach (Tenant::orderBy('name')->get() as $tenant) {
            // Names are not unique; the id keeps two sites with one name apart
            $label = $tenant->name ?: $tenant->id;
            $series[isset($series[$label]) ? "{$label} ({$tenant->id})" : $label] = $dates->map(function ($date) use ($values, $tenant, $metric) {
                $value = $values[$tenant->id][$date->toDateString()] ?? null;

                // No visits is a real zero; no requests means there is no response time to plot
                return $value !== null ? (int) $value : ($metric === 'views' ? 0 : null);
            })->all();
        }

        return ['labels' => $dates->map->format('j M')->all(), 'series' => $series];
    }
}
