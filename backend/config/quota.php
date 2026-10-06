<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage quota per site
    |--------------------------------------------------------------------------
    |
    | A site's usage is its uploads ({tenant_id}/ on this disk) plus its
    | database. A site may not upload past its limit. The limit is set per site in the central admin
    | (Platform → Tenants); sites without one get the default below.
    |
    */

    'disk' => 'public',

    // Count the site's database towards its usage as well as its files
    'include_database' => (bool) env('TENANT_STORAGE_INCLUDE_DATABASE', true),

    'default_limit_mb' => (int) env('TENANT_STORAGE_LIMIT_MB', 1024),

    /*
    | Total space you have to share out between sites, in MB. Left empty, the
    | size of the volume holding the disk is used. Set it when the disk is
    | object storage (S3/Tigris), where there is no volume to measure.
    */

    'capacity_mb' => env('STORAGE_CAPACITY_MB'),

    // Usage at which a site's storage meter turns to a warning (percent)
    'warn_at' => 80,

];
