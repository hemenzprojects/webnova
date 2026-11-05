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
                    ->where(fn ($q) => $q->whereNotNull('gateway_orders')->orWhere('payment_provider', 'stanbic'))
                    ->whereIn('payment_status', ['unpaid', 'failed'])
                    ->where('updated_at', '>', now()->subDays(3))
                    ->limit(50)
                    ->get()
                    ->each(function (MembershipRegistration $registration) use ($tenant, $registrar) {
                        // Every Stanbic attempt, newest first: the payer may have paid an earlier one in another tab
                        // (Paystack attempts are confirmed by Paystack's webhook)
                        $attempts = array_reverse(array_keys($registration->gateway_orders ?: [$registration->payment_reference => null]));
                        $attempts = array_filter($attempts, fn ($attempt) => $registration->paymentAttempt($attempt)['provider'] === 'stanbic'
                            && filled($registration->paymentAttempt($attempt)['order_id']));
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

                // Renewals started in the member portal
                \App\Plugins\Membership\Models\MembershipRenewal::query()
                    ->whereNotNull('gateway_orders')
                    ->whereIn('payment_status', ['unpaid', 'failed'])
                    ->where('updated_at', '>', now()->subDays(3))
                    ->limit(50)
                    ->get()
                    ->each(function (\App\Plugins\Membership\Models\MembershipRenewal $renewal) use ($tenant) {
                        $attempts = array_filter(array_reverse(array_keys($renewal->gateway_orders ?? [])),
                            fn ($attempt) => $renewal->paymentAttempt($attempt)['provider'] === 'stanbic' && filled($renewal->paymentAttempt($attempt)['order_id']));
                        foreach ($attempts as $attempt) {
                            try {
                                if (app(\App\Plugins\Membership\Support\Renewals::class)->confirm($renewal, $attempt)->payment_status === 'paid') {
                                    $this->line("{$tenant->getTenantKey()}: renewal {$renewal->reference} paid");

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
