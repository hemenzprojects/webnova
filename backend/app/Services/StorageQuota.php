<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use League\Flysystem\StorageAttributes;

/**
 * How much file storage each site may use, and how much it is using.
 *
 * Usage is the site's files ({tenant_id}/ on the quota disk and on the private disk) plus its
 * database. Files are measured from the disk itself, so every upload path is
 * covered and usage drops as soon as files are deleted.
 */
class StorageQuota
{
    private const MB = 1048576;

    public function limitBytes(Tenant $tenant): int
    {
        return ($tenant->storage_limit_mb ?: config('quota.default_limit_mb')) * self::MB;
    }

    public function usedBytes(Tenant $tenant): int
    {
        return $this->fileBytes($tenant) + $this->databaseBytes($tenant);
    }

    public function fileBytes(Tenant $tenant): int
    {
        $used = 0;

        foreach (array_unique(array_filter([config('quota.disk'), config('quota.private_disk')])) as $disk) {
            $files = Storage::disk($disk)->getDriver()->listContents($tenant->id, true);

            foreach ($files as $file) {
                /** @var StorageAttributes $file */
                if ($file->isFile()) {
                    $used += $file->fileSize() ?? 0;
                }
            }
        }

        return $used;
    }

    /**
     * Size of the site's database on disk. An empty site is already around
     * 10 MB: Postgres' own catalogue plus the empty tables and indexes.
     */
    public function databaseBytes(Tenant $tenant): int
    {
        if (! config('quota.include_database')) {
            return 0;
        }

        // Null when the database does not exist (yet)
        $size = DB::connection(config('tenancy.database.central_connection'))
            ->selectOne('select pg_database_size(oid) as size from pg_database where datname = ?', [$tenant->database()->getName()]);

        return (int) ($size->size ?? 0);
    }

    /**
     * For lists of many sites: usage that may be a few minutes old.
     */
    public function cachedUsedBytes(Tenant $tenant): int
    {
        return array_sum($this->cachedUsage($tenant));
    }

    /**
     * @return array{files: int, database: int}
     */
    public function cachedUsage(Tenant $tenant): array
    {
        return Cache::remember("storage-usage:{$tenant->id}", now()->addMinutes(5), fn () => [
            'files' => $this->fileBytes($tenant),
            'database' => $this->databaseBytes($tenant),
        ]);
    }

    /**
     * What all sites use together.
     *
     * @return array{files: int, database: int}
     */
    public function totalUsage(): array
    {
        $usage = Tenant::all()->map(fn (Tenant $tenant) => $this->cachedUsage($tenant));

        return ['files' => $usage->sum('files'), 'database' => $usage->sum('database')];
    }

    public function percentUsed(Tenant $tenant, ?int $used = null): int
    {
        $limit = $this->limitBytes($tenant);

        return $limit > 0 ? (int) floor(($used ?? $this->usedBytes($tenant)) / $limit * 100) : 100;
    }

    /**
     * The error to show if the current site cannot take $bytes more, or null
     * if it can. Always null outside a site (central admin, console).
     */
    public function errorForUpload(int $bytes): ?string
    {
        $tenant = tenancy()->tenant;

        if (! $tenant) {
            return null;
        }

        $used = $this->usedBytes($tenant);
        $limit = $this->limitBytes($tenant);

        if ($used + $bytes <= $limit) {
            return null;
        }

        return sprintf(
            'Storage limit reached: this site is using %s of its %s (files and content), and this upload needs %s. '
            .'Delete files you no longer need from the Media Library, or contact support for more space.',
            self::format($used),
            self::format($limit),
            self::format($bytes),
        );
    }

    /**
     * Total space available to share out between sites.
     */
    public function capacityBytes(): ?int
    {
        if ($mb = config('quota.capacity_mb')) {
            return (int) $mb * self::MB;
        }

        $disk = config('filesystems.disks.'.config('quota.disk'));

        if (($disk['driver'] ?? null) !== 'local') {
            return null;
        }

        $total = @disk_total_space($disk['root']);

        return $total ? (int) $total : null;
    }

    /**
     * The sum of every site's limit.
     */
    public function allocatedBytes(): int
    {
        return Tenant::all()->sum(fn (Tenant $tenant) => $this->limitBytes($tenant));
    }

    public static function format(int $bytes): string
    {
        return Number::fileSize($bytes, maxPrecision: 1);
    }
}
