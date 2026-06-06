<?php

namespace App\Jobs\Renewals;

use App\Mail\HostingReactivatedMail;
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
 * Reactivates a suspended hosting account once its overdue balance is paid
 * (Ticket 58).
 */
class UnsuspendPaidHostingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $hostingAccountId) {}

    public function handle(SuspensionService $suspension, NotificationService $notifications, AuditLogger $audit): void
    {
        $account = HostingAccount::with('user')->find($this->hostingAccountId);

        if (! $account || ! $account->isSuspended()) {
            return;
        }

        $ok = $suspension->unsuspend($account);

        if ($ok) {
            $audit->log('hosting.unsuspend.auto', $account, description: 'Auto-reactivated after payment');
            $notifications->send($account->user, new HostingReactivatedMail($account), 'hosting_reactivated');
        }
    }
}
