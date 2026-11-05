<?php

namespace App\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Site-wide CSS added after the theme and Branding styles, so it can override them.
 * Served by Api\CustomCssController and added to every page by frontend/app.vue.
 */
class CustomCss extends Page implements HasForms
{
    use InFunctionalAreaPage;

    protected static string $area = 'appearance';

    use InteractsWithForms;

    public const GROUP = 'custom_css';

    public const MAX_LENGTH = 100_000;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationLabel = 'Custom CSS';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Custom CSS';

    protected static string $view = 'filament.pages.settings-form';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'custom_css_enabled' => filter_var(Setting::get('custom_css_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'custom_css' => Setting::get('custom_css', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Additional CSS')
                    ->description('CSS added to every page of the public site, after the theme\'s and Branding\'s styles, so it wins over them. Theme colours are available as variables, e.g. var(--color-primary), var(--color-accent), var(--font-heading); to change one site-wide, write html:root { --color-primary: #123456; } (plain :root loses to Branding\'s colours). Installing a theme replaces the site\'s settings, so copy your CSS somewhere safe first.')
                    ->schema([
                        Forms\Components\Toggle::make('custom_css_enabled')
                            ->label('Use this CSS on the site')
                            ->helperText('Switch off to check the site without it, without losing what you wrote.'),
                        Forms\Components\Textarea::make('custom_css')
                            ->label('CSS')
                            ->rows(24)
                            ->maxLength(self::MAX_LENGTH)
                            ->placeholder(".page-title-bar h1 {\n  letter-spacing: 0.02em;\n}")
                            ->extraInputAttributes(['class' => 'font-mono text-sm', 'spellcheck' => 'false', 'autocapitalize' => 'off'])
                            ->helperText('CSS only — no <style> tags. Changes show on the next page load.'),
                    ]),
            ])
            ->statePath('data')
            // View-only roles see the CSS but cannot change it
            ->disabled(! static::canManage());
    }

    protected function getFormActions(): array
    {
        if (! static::canManage()) {
            return [];
        }

        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $this->authorizeManage();

        $data = $this->form->getState();

        Setting::set('custom_css_enabled', $data['custom_css_enabled'] ? '1' : '0', 'boolean', self::GROUP);
        Setting::set('custom_css', self::stripStyleTags((string) ($data['custom_css'] ?? '')), 'text', self::GROUP);

        Notification::make()
            ->title('Custom CSS saved')
            ->success()
            ->send();
    }

    /**
     * People often paste CSS together with its <style> wrapper; keep just the CSS.
     */
    public static function stripStyleTags(string $css): string
    {
        return trim(preg_replace('#</?style\b[^>]*>#i', '', $css));
    }
}
