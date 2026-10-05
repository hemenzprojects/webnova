<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    /**
     * Get all active menus indexed by location
     */
    public function index()
    {
        $menus = Cache::remember('menus.all', 3600, function () {
            return Menu::where('is_active', true)
                ->with(['rootItems.children'])
                ->get()
                ->mapWithKeys(function ($menu) {
                    return [
                        $menu->location => [
                            'id' => $menu->id,
                            'name' => $menu->name,
                            'location' => $menu->location,
                            'items' => $menu->getNestedItems(),
                        ]
                    ];
                });
        });

        return response()->json($menus);
    }

    /**
     * Get menu by location with nested items
     */
    public function show($location)
    {
        $menu = Cache::remember('menu.' . $location, 3600, function () use ($location) {
            return Menu::where('location', $location)
                ->where('is_active', true)
                ->with(['rootItems.children'])
                ->first();
        });

        // No menu at this location is a normal state (the frontend falls back
        // to its default links), so it is not reported as an error
        if (!$menu) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $menu->id,
            'name' => $menu->name,
            'location' => $menu->location,
            'items' => $menu->getNestedItems(),
        ]);
    }
}
