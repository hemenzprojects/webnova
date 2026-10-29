<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Support\Registrar;
use App\Plugins\PluginManager;
use Illuminate\Console\Command;
use Throwable;

/**
 * Confirms online membership payments whose payer never came back to the
 * website (closed the browser on the bank's page). Paystack tells us by
 * webhook; Stanbic's N-Genius is asked here, about every attempt of the
 * unpaid applications sent to Stanbic in the last 3 days. Runs every 5 minutes from the scheduler.
 */
class CheckMembershipPayments extends Command
{
    protected $signature = 'membership:check-payments {--tenant= : Only this site}';

    protected $description = 'Check unconfirmed Stanbic (N-Genius) membership payments with the bank';

    public function handle(PluginManager $plugins, Registrar $registrar): int
    {
        $tenants = Tenant::query()->when($this->option('tenant'), fn ($q, $id) => $q->whereKey($id))->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $plugins, $registrar) {
                if (! $plugins->isActive('membership')) {
                    return;
                }

                MembershipRegistration::query()
                    ->where('payment_provider', 'stanbic')
                    ->whereNotNull('gateway_order_id')
                    ->whereIn('payment_status', ['unpaid', 'failed'])
                    ->where('updated_at', '>', now()->subDays(3))
                    ->limit(50)
                    ->get()
                    ->each(function (MembershipRegistration $registration) use ($tenant, $registrar) {
                        // Every attempt, newest first: the payer may have paid an earlier one in another tab
                        $attempts = array_reverse(array_keys($registration->gateway_orders ?: [$registration->payment_reference => $registration->gateway_order_id]));
                        foreach ($attempts as $attempt) {
                            try {
                                if ($registrar->confirmPayment($registration, $attempt)->payment_status === 'paid') {
                                    $this->line("{$tenant->getTenantKey()}: {$registration->reference} paid");

                                    return;
                                }
                            } catch (Throwable $e) {
                                report($e);
                            }
                        }
                    });
            });
        }

        return self::SUCCESS;
    }
}
