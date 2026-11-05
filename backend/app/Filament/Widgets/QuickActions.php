<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\EventResource;
use App\Filament\Resources\MediaResource;
use App\Filament\Resources\NewsResource;
use App\Filament\Resources\PageResource;
use Filament\Widgets\Widget;

/**
 * Site dashboard: shortcuts to the things people do most, next to the welcome card.
 */
class QuickActions extends Widget
{
    protected static ?int $sort = -2;

    protected static string $view = 'filament.widgets.quick-actions';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getViewData(): array
    {
        $create = fn (string $resource, string $label, string $icon) => $resource::canCreate()
            ? ['label' => $label, 'icon' => $icon, 'url' => $resource::getUrl('create')]
            : null;

        return [
            'actions' => array_values(array_filter([
                $create(PageResource::class, 'New page', 'heroicon-m-document-plus'),
                $create(NewsResource::class, 'New news post', 'heroicon-m-newspaper'),
                $create(EventResource::class, 'New event', 'heroicon-m-calendar-days'),
                MediaResource::canViewAny()
                    ? ['label' => 'Media library', 'icon' => 'heroicon-m-photo', 'url' => MediaResource::getUrl()]
                    : null,
            ])),
            'siteUrl' => request()->getSchemeAndHttpHost(),
        ];
    }
}
