<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all menu items
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Get only root-level menu items (no parent)
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    /**
     * Get nested menu items structure for API
     */
    public function getNestedItems(): array
    {
        $items = $this->rootItems()
            ->with('children')
            ->where('is_published', true)
            ->get();

        return $items->map(function ($item) {
            return $this->buildTree($item);
        })->toArray();
    }

    /**
     * Recursively build menu tree
     */
    private function buildTree(MenuItem $item): array
    {
        $data = [
            'id' => $item->id,
            'label' => $item->label,
            'type' => $item->type,
            'url' => $item->getUrl(),
            'icon' => $item->icon,
            'open_in_new_tab' => $item->open_in_new_tab,
            'css_class' => $item->css_class,
            'target_id' => $item->target_id,
            'children' => [],
        ];

        if ($item->children && $item->children->count() > 0) {
            $data['children'] = $item->children
                ->where('is_published', true)
                ->sortBy('order')
                ->map(function ($child) {
                    return $this->buildTree($child);
                })->values()->toArray();
        }

        return $data;
    }
}
