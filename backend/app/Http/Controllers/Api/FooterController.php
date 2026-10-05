<?php

namespace App\Http\Controllers\Api;

use App\Filament\Pages\FooterSettings;
use App\Http\Controllers\Controller;
use App\Models\Branding;
use App\Models\Menu;
use App\Models\Setting;
use App\Support\SocialPlatforms;

class FooterController extends Controller
{
    /**
     * HTML allowed in the text widget (what its editor toolbar can produce)
     */
    protected const TEXT_TAGS = '<p><br><div><strong><b><em><i><u><s><del><a><ul><ol><li>';

    /**
     * Get the footer layout: one row of columns, each holding a stack of widgets
     */
    public function index()
    {
        $settings = Setting::where('group', FooterSettings::GROUP)->pluck('value', 'key');

        // null means the footer has never been configured, so the frontend
        // keeps its built-in default layout
        $columns = null;

        if ($settings->has('footer_columns')) {
            $configured = collect(json_decode((string) $settings['footer_columns'], true) ?: [])
                ->take(FooterSettings::MAX_COLUMNS);

            $menus = Menu::where('is_active', true)
                ->whereIn('id', $configured->pluck('widgets')->flatten(1)->pluck('data.menu_id')->filter())
                ->get()
                ->keyBy('id');

            $columns = $configured->map(fn ($column) => [
                'widgets' => collect($column['widgets'] ?? [])
                    ->map(fn ($widget) => $this->widget($widget, $menus))
                    ->filter()
                    ->values(),
            ])->values();
        }

        return response()->json([
            'columns' => $columns,
            'copyright' => $settings->get('footer_copyright') ?: null,
        ]);
    }

    /**
     * Build one widget. Unknown widgets and menus that are missing or
     * inactive are dropped.
     */
    protected function widget(array $widget, $menus): ?array
    {
        $type = $widget['type'] ?? null;
        $data = $widget['data'] ?? [];
        $heading = ($data['heading'] ?? '') ?: null;

        if (! in_array($type, FooterSettings::WIDGETS, true)) {
            return null;
        }

        return match ($type) {
            'heading' => ['type' => 'heading', 'text' => $data['text'] ?? ''],
            'text' => ['type' => 'text', 'html' => strip_tags((string) ($data['text'] ?? ''), self::TEXT_TAGS)],
            'social' => [
                'type' => 'social',
                'heading' => $heading,
                'links' => SocialPlatforms::fromBranding(Branding::settings()),
            ],
            'menu' => ($menu = $menus->get($data['menu_id'] ?? null))
                ? ['type' => 'menu', 'heading' => $heading, 'items' => $menu->getNestedItems()]
                : null,
            default => ['type' => $type, 'heading' => $heading],
        };
    }
}
