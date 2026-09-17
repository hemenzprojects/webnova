<?php

namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuObserver
{
    /**
     * Handle the Menu "saved" event.
     */
    public function saved(Menu $menu): void
    {
        $this->clearCache($menu);
    }

    /**
     * Handle the Menu "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        $this->clearCache($menu);
    }

    /**
     * Clear menu cache
     */
    private function clearCache(Menu $menu): void
    {
        // Clear cache for this specific menu location
        Cache::forget('menu.' . $menu->location);

        // Also clear the all menus cache
        Cache::forget('menus.all');
    }
}
