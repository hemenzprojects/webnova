<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'mobile_code', 'app_key'];
    }

    // Database name: customer_<alias>  e.g. customer_cda
    public function database(): \Stancl\Tenancy\Contracts\TenantDatabaseManager
    {
        return app(\Stancl\Tenancy\Database\TenantDatabaseManagers\PostgreSQLDatabaseManager::class);
    }
}