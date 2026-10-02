<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;

class ThemeController extends Controller
{
    public function __construct(private ThemeService $themes) {}

    /**
     * Return the active theme for the current tenant, plus the list of
     * available themes so the admin picker can render options.
     */
    public function index()
    {
        return response()->json([
            'active' => $this->themes->forCurrentTenant(),
            'available' => collect($this->themes->all())
                ->map(fn ($theme, $slug) => [
                    'slug' => $slug,
                    'name' => $theme['name'] ?? $slug,
                    'description' => $theme['description'] ?? null,
                    'preview' => $theme['preview'] ?? null,
                ])
                ->values(),
        ]);
    }
}