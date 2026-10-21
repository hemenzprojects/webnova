<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

/**
 * Runs after a tenant is deleted (once its database has been dropped and its
 * domains removed by the foreign key): removes everything else the site left
 * behind — uploaded files, theme backups, Site Health figures, cached usage.
 */
class DeleteTenantData
{
    public function __construct(protected TenantWithDatabase $tenant)
    {
    }

    public function handle(): void
    {
        $id = (string) $this->tenant->getTenantKey();

        // An empty id would point at the root of the disk.
        if (! preg_match('/^[a-z0-9-]+$/', $id)) {
            return;
        }

        Storage::disk('public')->deleteDirectory($id);
        Storage::disk('local')->deleteDirectory("theme-backups/{$id}");

        $central = DB::connection(config('tenancy.database.central_connection'));
        foreach (['site_stats', 'site_page_views', 'site_slow_queries'] as $table) {
            $central->table($table)->where('tenant_id', $id)->delete();
        }

        Cache::forget("storage-usage:{$id}");
    }
}
