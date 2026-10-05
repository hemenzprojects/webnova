<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\PluginManager;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

/**
 * Tenant admin: switch plugins on and off for this site.
 */
class Plugins extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'system';

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.plugins';

    public function getPlugins(): array
    {
        $manager = app(PluginManager::class);

        return collect($manager->availableFor(tenant()))
            ->map(fn ($plugin, $key) => [
                'key' => $key,
                'name' => $plugin->name(),
                'description' => $plugin->description(),
                'icon' => $plugin->icon(),
                'active' => $manager->isActive($key),
            ])
            ->values()
            ->all();
    }

    public function activateAction(): Action
    {
        return Action::make('activate')
            ->authorize(fn () => static::canManage())
            ->label('Activate')
            ->icon('heroicon-o-power')
            ->action(function (array $arguments) {
                try {
                    app(PluginManager::class)->activate($arguments['plugin']);
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->title('The plugin could not be activated')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Plugin activated')->body('Its pages are now in the menu.')->success()->send();
                // Reload so the menu picks up the plugin's pages
                $this->redirect(static::getUrl());
            });
    }

    public function deactivateAction(): Action
    {
        return Action::make('deactivate')
            ->authorize(fn () => static::canManage())
            ->label('Deactivate')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Deactivate this plugin?')
            ->modalDescription('Its pages disappear from the menu and its forms stop working on your site. Nothing is deleted: activate it again to carry on where you left off.')
            ->action(function (array $arguments) {
                app(PluginManager::class)->deactivate($arguments['plugin']);
                Notification::make()->title('Plugin deactivated')->success()->send();
                $this->redirect(static::getUrl());
            });
    }
}
