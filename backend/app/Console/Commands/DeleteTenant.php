<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class DeleteTenant extends Command
{
    protected $signature = 'tenants:delete
                            {tenant : Tenant id, e.g. garnet}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete a site with its database, files, theme backups and statistics';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));
        if (! $tenant) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $domains = $tenant->domains->pluck('domain')->join(', ') ?: 'no domains';
        $this->warn("This permanently deletes \"{$tenant->id}\" ({$domains}): its database customer_{$tenant->id}, uploaded files, theme backups and statistics. This cannot be undone.");

        if (! $this->option('force') && $this->ask('Type the tenant id to confirm') !== $tenant->id) {
            $this->error('Not confirmed. Nothing was deleted.');

            return self::FAILURE;
        }

        $tenant->delete();

        $this->info('Site deleted.');

        return self::SUCCESS;
    }
}
