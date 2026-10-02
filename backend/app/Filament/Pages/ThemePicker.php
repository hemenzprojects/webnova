<?php

namespace App\Filament\Pages;

use App\Services\ThemeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ThemePicker extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Theme';

    protected static ?string $title = 'Site Theme';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.theme-picker';

    public ?string $theme_slug = null;

    public function mount(): void
    {
        $tenant = tenant();
        $this->theme_slug = $tenant?->theme_slug ?? 'default';
        $this->form->fill(['theme_slug' => $this->theme_slug]);
    }

    public function form(Form $form): Form
    {
        $themes = app(ThemeService::class)->all();

        $options = [];
        $descriptions = [];
        foreach ($themes as $slug => $theme) {
            $options[$slug] = $theme['name'] ?? $slug;
            $descriptions[$slug] = $theme['description'] ?? '';
        }

        return $form
            ->schema([
                Radio::make('theme_slug')
                    ->label('Choose a theme')
                    ->options($options)
                    ->descriptions($descriptions)
                    ->required(),
            ])
            ->statePath('data');
    }

    public array $data = [];

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Apply theme')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $tenant = tenant();
        if (! $tenant) {
            Notification::make()->title('No active tenant')->danger()->send();
            return;
        }

        $slug = $this->data['theme_slug'] ?? 'default';
        app(ThemeService::class)->applyToTenant($tenant, $slug);
        $this->theme_slug = $slug;

        Notification::make()
            ->title('Theme applied')
            ->body('Your site is now using the ' . $slug . ' theme.')
            ->success()
            ->send();
    }
}