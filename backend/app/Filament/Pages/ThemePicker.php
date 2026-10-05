<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Services\ThemeInstaller;
use App\Services\ThemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Throwable;

class ThemePicker extends Page
{
    use InFunctionalAreaPage;

    protected static string $area = 'appearance';

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Themes';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Themes';

    protected static string $view = 'filament.pages.theme-picker';

    public function getActiveSlug(): string
    {
        return tenant()?->theme_slug ?? 'default';
    }

    /**
     * Themes for the cards, with whether each has starter content to install.
     */
    public function getThemes(): array
    {
        $installer = app(ThemeInstaller::class);

        return collect(app(ThemeService::class)->all())
            ->map(fn (array $theme, string $slug) => $theme + [
                'slug' => $slug,
                'installable' => $installer->hasPackage($slug),
            ])
            ->values()
            ->all();
    }

    /**
     * Backups of this site's content, newest first.
     */
    public function getBackups(): array
    {
        $tenant = tenant();
        if (! $tenant) {
            return [];
        }

        return collect(Storage::disk('local')->files("theme-backups/{$tenant->id}"))
            ->reverse()
            ->map(function (string $path) {
                // Filename: Ymd_His-{theme}.json
                [$stamp, $theme] = array_pad(explode('-', basename($path, '.json'), 2), 2, '');

                return [
                    'file' => basename($path),
                    'theme' => app(ThemeService::class)->get($theme)['name'] ?? $theme,
                    'date' => \Carbon\Carbon::createFromFormat('Ymd_His', $stamp)?->format('j M Y, H:i'),
                ];
            })
            ->values()
            ->all();
    }

    public function installAction(): Action
    {
        return Action::make('install')
            ->authorize(fn () => static::canManage())
            ->label('Install')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('danger')
            ->modalHeading(fn (array $arguments) => 'Install the ' . (app(ThemeService::class)->get($arguments['theme'])['name'] ?? '') . ' theme?')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            ->modalSubmitActionLabel('Clear my site and install')
            ->form(fn () => [
                Placeholder::make('warning')
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<p>Installing a theme <strong>replaces your whole site</strong> with the theme\'s starter content:</p>'
                        . '<ul style="list-style:disc;margin:.5rem 0 .5rem 1.25rem">'
                        . '<li>All pages, menus, services, news, events, members and team members are deleted.</li>'
                        . '<li>Branding, header, footer and sidebar settings are reset.</li>'
                        . '<li>The media library is emptied (uploaded files stay on the server).</li>'
                        . '</ul>'
                        . '<p>Admin users and passwords are kept. A backup is saved first, so you can restore it from this page.</p>'
                    )),
                TextInput::make('confirm')
                    ->label(new HtmlString('Type <strong>' . e(tenant('id')) . '</strong> to confirm'))
                    ->required()
                    ->in([tenant('id')])
                    ->validationMessages(['in' => 'Type the site ID exactly as shown.'])
                    ->autocomplete(false),
            ])
            ->action(function (array $arguments) {
                try {
                    app(ThemeInstaller::class)->install(tenant(), $arguments['theme']);
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()
                        ->title('The theme could not be installed')
                        ->body('Your site was not changed. ' . $e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Theme installed')
                    ->body('Your site now uses the new theme and its starter pages. Edit them under Pages.')
                    ->success()
                    ->send();
            });
    }

    public function restoreAction(): Action
    {
        return Action::make('restore')
            ->authorize(fn () => static::canManage())
            ->label('Restore')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Restore this backup?')
            ->modalDescription('Your current pages, menus, content and settings are replaced by the backup, and the theme switches back to the one in use when it was made. A backup of the current site is saved first.')
            ->modalSubmitActionLabel('Restore')
            ->action(function (array $arguments) {
                $tenant = tenant();
                $file = basename($arguments['file']);
                $path = Storage::disk('local')->path("theme-backups/{$tenant->id}/{$file}");

                try {
                    $installer = app(ThemeInstaller::class);
                    $installer->backup($tenant);
                    $installer->restore($tenant, $path);
                } catch (Throwable $e) {
                    report($e);
                    Notification::make()->title('The backup could not be restored')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Backup restored')->success()->send();
            });
    }
}
