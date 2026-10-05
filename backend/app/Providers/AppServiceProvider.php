<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Observers\MenuObserver;
use App\Observers\MenuItemObserver;
use Illuminate\Support\ServiceProvider;

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
    }
}
