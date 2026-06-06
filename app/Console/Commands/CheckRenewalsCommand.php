<?php

namespace App\Console\Commands;

use App\Jobs\Renewals\SendRenewalReminderJob;
use App\Jobs\Renewals\SuspendOverdueHostingJob;
use App\Services\Renewals\RenewalService;
use Illuminate\Console\Command;

class CheckRenewalsCommand extends Command
{
    protected $signature = 'renewals:check';

    protected $description = 'Send renewal reminders and suspend hosting that is overdue past the grace period.';

    public function handle(RenewalService $renewals): int
    {
        // Reminder windows from config (e.g. 30,14,7,3,1) plus the renewal day.
        $windows = array_unique(array_merge(
            config('billing.renewal_reminder_days_before', []),
            [0],
        ));

        $reminders = 0;
        foreach ($windows as $days) {
            foreach ($renewals->dueInDays((int) $days) as $service) {
                SendRenewalReminderJob::dispatch(
                    $service['user']->id,
                    $service['name'],
                    $service['date']->toDateString(),
                    $service['amount'],
                    (int) $days,
                    sprintf('%s:%d:%d:%s', $service['type'], $service['id'], $days, $service['date']->toDateString()),
                );
                $reminders++;
            }
        }

        // Suspend hosting that is overdue beyond the grace period.
        $suspended = 0;
        foreach ($renewals->overdueHostingPastGrace() as $account) {
            SuspendOverdueHostingJob::dispatch($account->id);
            $suspended++;
        }

        $this->info("Renewal check complete: {$reminders} reminder(s) queued, {$suspended} overdue account(s) queued for suspension.");

        return self::SUCCESS;
    }
}
