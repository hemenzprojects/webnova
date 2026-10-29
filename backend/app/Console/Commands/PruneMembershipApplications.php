<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\PluginManager;
use Illuminate\Console\Command;

/**
 * Data retention (Membership plugin): deletes rejected and never-paid
 * applications, with their documents, once they are older than the site's
 * retention period (Membership → Settings → Privacy and data). Applications
 * that led to a member account are kept. Runs daily from the scheduler.
 */
class PruneMembershipApplications extends Command
{
    protected $signature = 'membership:prune-applications {--tenant= : Only this site} {--dry-run : Count, do not delete}';

    protected $description = 'Delete rejected and never-paid membership applications past each site\'s retention period';

    public function handle(PluginManager $plugins): int
    {
        $tenants = Tenant::query()->when($this->option('tenant'), fn ($q, $id) => $q->whereKey($id))->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $plugins) {
                if (! $plugins->isActive('membership')) {
                    return;
                }
                $months = (int) MembershipSettings::get('retention_months');
                if ($months <= 0) {
                    return;
                }

                $query = MembershipRegistration::query()
                    ->where('created_at', '<', now()->subMonths($months))
                    ->whereDoesntHave('memberAccount')
                    ->where(fn ($q) => $q->where('status', 'rejected')
                        ->orWhere(fn ($q) => $q->where('status', 'pending')->whereIn('payment_status', ['unpaid', 'failed'])));

                $count = $query->count();
                if (! $this->option('dry-run')) {
                    // One by one, so each application's documents go too
                    $query->lazyById()->each->delete();
                }
                if ($count) {
                    $this->line("{$tenant->getTenantKey()}: " . ($this->option('dry-run') ? 'would delete' : 'deleted') . " {$count} application(s) older than {$months} months");
                }
            });
        }

        return self::SUCCESS;
    }
}
