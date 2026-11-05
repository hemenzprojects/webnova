<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Site dashboard: the content most recently created or edited, to pick work back up.
 */
class RecentChanges extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.recent-changes';

    private const LIMIT = 8;

    public static function canView(): bool
    {
        return SiteOverview::canView();
    }

    protected function getViewData(): array
    {
        $items = collect();
        foreach (SiteOverview::TYPES as $resource => [$label, $title, $live, $icon]) {
            if (! $resource::canViewAny()) {
                continue;
            }
            $manage = $resource::canManage();
            $isPage = $resource::getModel() === \App\Models\Page::class;

            $resource::getModel()::query()->latest('updated_at')->limit(self::LIMIT)->get()
                ->each(fn ($record) => $items->push([
                    'type' => str($label)->singular()->toString(),
                    'icon' => $icon,
                    'title' => $record->{$title},
                    'live' => (bool) $record->{$live},
                    'updated_at' => $record->updated_at,
                    'created' => $record->created_at?->equalTo($record->updated_at),
                    'url' => $manage ? $resource::getUrl('edit', ['record' => $record]) : null,
                    'builder' => $manage && $isPage && $record->template_type === 'builder' ? "/elementor/{$record->id}" : null,
                ]));
        }

        return ['items' => $items->sortByDesc('updated_at')->take(self::LIMIT)->values()];
    }
}
