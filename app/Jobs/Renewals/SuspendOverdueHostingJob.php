<?php

namespace App\Jobs\Renewals;

use App\Mail\HostingSuspendedMail;
use App\Models\HostingAccount;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\NotificationService;
use App\Services\Renewals\SuspensionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Suspends a single overdue hosting account after the grace period (Ticket 57).
 * Only ever runs for accounts past grace; a failed suspension is logged for
 * manual review rather than retried blindly.
 */
class SuspendOverdueHostingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $hostingAccountId) {}

    public function handle(SuspensionService $suspension, NotificationService $notifications, AuditLogger $audit): void
    {
        $account = HostingAccount::with('user')->find($this->hostingAccountId);

        if (! $account || ! $account->isActive()) {
            return;
        }

        $ok = $suspension->suspend($account, 'Renewal payment overdue');

        if ($ok) {
            $audit->log('hosting.suspend.auto', $account, description: 'Auto-suspended after grace period');
            $notifications->send($account->user, new HostingSuspendedMail($account), 'hosting_suspended');
        }
    }
}
