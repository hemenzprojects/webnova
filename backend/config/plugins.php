<?php

/**
 * Installed plugins. The central admin chooses which of these each tenant may
 * use (Tenants → Allowed plugins); the tenant's admin switches them on under
 * Plugins. See App\Plugins\Plugin.
 */
return [
    'plugins' => [
        App\Plugins\Membership\MembershipPlugin::class,
    ],
];
