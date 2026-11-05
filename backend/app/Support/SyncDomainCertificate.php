<?php

namespace App\Support;

use App\Services\FlyCertificates;
use Stancl\Tenancy\Events\DomainCreated;
use Stancl\Tenancy\Events\DomainDeleted;

/**
 * Keeps Fly's certificates in step with the domains table. A failure never
 * blocks the domain change: the tenant page shows "No certificate" and has
 * a button to request it again.
 */
class SyncDomainCertificate
{
    public function __construct(private FlyCertificates $certificates)
    {
    }

    public function handle(DomainCreated|DomainDeleted $event): void
    {
        try {
            $event instanceof DomainCreated
                ? $this->certificates->add($event->domain->domain)
                : $this->certificates->remove($event->domain->domain);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
