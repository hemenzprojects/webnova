<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MembershipNotice;
use App\Plugins\Membership\Support\MembershipMailer;
use App\Plugins\Membership\Support\MembershipSettings;
use App\Plugins\Membership\Support\Renewals;
use App\Plugins\Membership\Support\Standing;
use App\Plugins\PluginManager;
use Illuminate\Console\Command;
use Throwable;

/**
 * Renewals (Membership): refreshes every member's standing and emails the
 * renewal notices that are due, each once per member, membership year and
 * kind (membership_notices, shown on the member's page):
 *
 * - reminder:  due, from N days before the due date
 * - overdue:   overdue, from the notice date (1 month after the due date)
 * - suspended: suspended for not paying (3 months after)
 * - forfeited: whole years unpaid
 *
 * Only the notice for where a member stands now is sent (after a pause in the
 * scheduler nobody gets a backlog of older ones). At most "renewal_notice_limit"
 * emails per site per run; the rest follow the next day. Runs daily.
 */
class SendRenewalNotices extends Command
{
    protected $signature = 'membership:renewal-notices {--tenant= : Only this site} {--dry-run : List, do not send}';

    protected $description = 'Refresh members\' standing and email the renewal reminders, overdue, suspension and forfeiture notices that are due';

    public function handle(PluginManager $plugins, MembershipMailer $mailer): int
    {
        $tenants = Tenant::query()->when($this->option('tenant'), fn ($q, $id) => $q->whereKey($id))->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $plugins, $mailer) {
                if (! $plugins->isActive('membership') || ! Renewals::enabled()) {
                    return;
                }
                $limit = max(0, (int) MembershipSettings::get('renewal_notice_limit')) ?: PHP_INT_MAX;
                $sent = 0;

                MemberAccount::query()->with(['membershipType', 'sponsor'])->lazyById(200)->each(function (MemberAccount $account) use ($tenant, $mailer, $limit, &$sent) {
                    $standing = Renewals::refresh($account);
                    // Seats are covered by their corporate member, who gets the notices; suspended accounts are the admins' call
                    if ($sent >= $limit || $account->sponsor_account_id || ! $account->isActive()) {
                        return;
                    }

                    [$kind, $year] = $this->noticeFor($standing) ?? [null, null];
                    if (! $kind || MembershipNotice::where(['member_account_id' => $account->id, 'year' => $year, 'kind' => $kind])->exists()) {
                        return;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("{$tenant->getTenantKey()}: would send {$kind} ({$year}) to {$account->member_number} {$account->email}");

                        return;
                    }

                    try {
                        $ok = $mailer->renewalNotice($account, $kind, $standing);
                    } catch (Throwable $e) {
                        report($e);
                        $ok = false;
                    }
                    // Recorded either way, so a failing address is not tried every day; "sent" says whether it went out
                    MembershipNotice::create(['member_account_id' => $account->id, 'year' => $year, 'kind' => $kind, 'email' => $account->email, 'sent' => $ok, 'sent_at' => now()]);
                    $sent++;
                    $this->line("{$tenant->getTenantKey()}: {$kind} ({$year}) " . ($ok ? 'sent' : 'NOT sent') . " to {$account->member_number}");
                });
            });
        }

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: int}|null the notice due for this standing, and its membership year */
    private function noticeFor(Standing $standing): ?array
    {
        $today = now()->startOfDay();
        $reminderDays = (int) MembershipSettings::get('renewal_reminder_days');

        return match ($standing->state) {
            Standing::DUE => $reminderDays > 0 && $standing->dueDate && $today->gte($standing->dueDate->subDays($reminderDays))
                ? ['reminder', $standing->unpaidYear] : null,
            Standing::OVERDUE => $standing->noticeOn && $today->gte($standing->noticeOn) ? ['overdue', $standing->unpaidYear] : null,
            Standing::SUSPENDED => ['suspended', $standing->unpaidYear],
            Standing::FORFEITED => ['forfeited', $standing->unpaidYear],
            default => null,
        };
    }
}
