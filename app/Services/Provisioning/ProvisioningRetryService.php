<?php

namespace App\Services\Provisioning;

use App\Enums\ProvisioningStatus;
use App\Models\ProvisioningJob;

/**
 * Re-queues failed/manual-review provisioning steps. Used by the admin
 * "Retry" action and the scheduled provisioning:retry-failed command. Each
 * step job is idempotent, so a retry never duplicates external resources.
 */
class ProvisioningRetryService
{
    public function __construct(private readonly ProvisioningOrchestrator $orchestrator) {}

    /**
     * Reset a step to pending and dispatch its job. Returns false if the step
     * is not in a retryable state.
     */
    public function retry(ProvisioningJob $job): bool
    {
        if (! $job->canRetry()) {
            return false;
        }

        if ($job->attempts >= $job->max_attempts) {
            // Allow the admin one explicit manual retry beyond max_attempts.
            $job->forceFill(['max_attempts' => $job->attempts + 1])->save();
        }

        $job->forceFill([
            'status' => ProvisioningStatus::Pending->value,
            'error_message' => null,
            'failed_at' => null,
        ])->save();

        $jobClass = $this->orchestrator->jobClassFor($job->job_type);
        $jobClass::dispatch($job->order_id);

        return true;
    }
}
