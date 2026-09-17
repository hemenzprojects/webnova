<?php

namespace App\Observers;

use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

class MenuItemObserver
{
    /**
     * Handle the MenuItem "saved" event.
     */
    public function saved(MenuItem $menuItem): void
    {
        $this->clearCache($menuItem);
    }

    /**
     * Handle the MenuItem "deleted" event.
     */
    public function deleted(MenuItem $menuItem): void
    {
        $this->clearCache($menuItem);
    }

    /**
     * Clear menu cache for the parent menu
     */
    private function clearCache(MenuItem $menuItem): void
    {
        // Load the menu relationship if not already loaded
        if (!$menuItem->relationLoaded('menu')) {
            $menuItem->load('menu');
        }

        // Clear cache for the menu this item belongs to
        if ($menuItem->menu) {
            Cache::forget('menu.' . $menuItem->menu->location);
        }

        // Also clear the all menus cache
        Cache::forget('menus.all');
    }
}
