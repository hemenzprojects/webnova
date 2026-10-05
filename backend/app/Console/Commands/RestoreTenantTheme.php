<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\ThemeInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RestoreTenantTheme extends Command
{
    protected $signature = 'tenants:restore-theme
                            {tenant : Tenant id, e.g. garnet}
                            {file? : Backup file; omit to list this tenant\'s backups}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the content a tenant had before a theme install';

    public function handle(ThemeInstaller $installer): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $file = $this->argument('file');
        if (! $file) {
            $backups = Storage::disk('local')->files("theme-backups/{$tenant->id}");
            if (! $backups) {
                $this->line('No backups for this tenant.');
            }
            foreach (array_reverse($backups) as $backup) {
                $this->line(Storage::disk('local')->path($backup));
            }

            return self::SUCCESS;
        }

        if (! File::exists($file)) {
            $this->error("Backup file not found: {$file}");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("This replaces the current content of \"{$tenant->id}\" with the backup. Continue?")) {
            return self::FAILURE;
        }

        $tenant->run(fn () => $installer->restore($tenant, $file));

        $this->info('Backup restored.');

        return self::SUCCESS;
    }
}
