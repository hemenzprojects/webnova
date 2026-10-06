<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Observers\MenuObserver;
use App\Observers\MenuItemObserver;
use App\Services\SiteHealth;
use App\Services\StorageQuota;
use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One instance per request: it caches which plugins are active
        $this->app->scoped(\App\Plugins\PluginManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);

        // Site Health: log slow queries against a site's own database
        DB::listen(function (QueryExecuted $query): void {
            if ($query->time >= config('health.slow_query_ms') && $query->connectionName === 'tenant') {
                app(SiteHealth::class)->recordSlowQuery(tenancy()->tenant?->id, $query->sql, $query->time);
            }
        });

        // Storage quota: every FileUpload in the admin refuses files that
        // would take the site past its limit
        FileUpload::configureUsing(function (FileUpload $component): void {
            $component->rule(fn (FileUpload $component) => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                $bytes = collect($component->getState())
                    ->filter(fn ($file) => $file instanceof TemporaryUploadedFile)
                    ->sum(fn (TemporaryUploadedFile $file) => $file->getSize());

                if ($bytes && $error = app(StorageQuota::class)->errorForUpload($bytes)) {
                    $fail($error);
                }
            });
        });
    }
}
