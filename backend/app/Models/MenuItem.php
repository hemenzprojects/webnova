<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'icon',
        'type',
        'url',
        'page_id',
        'open_in_new_tab',
        'css_class',
        'target_id',
        'is_published',
        'order',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * Get the menu this item belongs to
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent menu item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('order');
    }

    /**
     * Get the page this item links to
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Generate URL based on menu item type
     */
    public function getUrl(): ?string
    {
        return match ($this->type) {
            'page' => $this->page && $this->page->is_published ? '/' . $this->page->slug : null,
            'custom' => $this->url,
            'category' => null, // Categories are non-clickable headers
            'news' => '/news',
            'events' => '/events',
            'services' => '/services',
            'members' => '/members',
            default => null,
        };
    }

    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Get nesting depth level
     */
    public function getDepth(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parent) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }

    /**
     * Scope to get only published items
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get only root items
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
