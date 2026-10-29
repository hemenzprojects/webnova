<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ThemeInstaller;
use Illuminate\Console\Command;

class InstallTenantTheme extends Command
{
    protected $signature = 'tenants:install-theme
                            {tenant : Tenant id, e.g. garnet}
                            {theme : Theme slug, e.g. edubright}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Replace a tenant\'s content with a theme\'s starter pages (backs up first; users are kept)';

    public function handle(ThemeInstaller $installer): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $theme = $this->argument('theme');
        if (! $this->option('force') && ! $this->confirm("This clears all pages, menus, content, media records and settings for \"{$tenant->id}\" and installs \"{$theme}\". Continue?")) {
            return self::FAILURE;
        }

        $backup = $tenant->run(fn () => $installer->install($tenant, $theme));

        $this->info("Installed \"{$theme}\" for \"{$tenant->id}\".");
        $this->line("Backup of the previous content: {$backup}");
        $this->line("Restore it with: php artisan tenants:restore-theme {$tenant->id} \"{$backup}\"");

        return self::SUCCESS;
    }
}
