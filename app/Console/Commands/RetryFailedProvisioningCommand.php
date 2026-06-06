<?php

namespace App\Console\Commands;

use App\Enums\ProvisioningStatus;
use App\Models\ProvisioningJob;
use App\Services\Provisioning\ProvisioningRetryService;
use Illuminate\Console\Command;

class RetryFailedProvisioningCommand extends Command
{
    protected $signature = 'provisioning:retry-failed';

    protected $description = 'Automatically retry transient provisioning failures (never manual-review steps).';

    public function handle(ProvisioningRetryService $retry): int
    {
        // Only auto-retry "failed" steps that still have attempts left.
        // "manual_review" steps require a human decision and are left alone.
        $jobs = ProvisioningJob::where('status', ProvisioningStatus::Failed->value)
            ->whereColumn('attempts', '<', 'max_attempts')
            ->get();

        $count = 0;
        foreach ($jobs as $job) {
            if ($retry->retry($job)) {
                $count++;
            }
        }

        $this->info("Re-queued {$count} failed provisioning step(s).");

        return self::SUCCESS;
    }
}
