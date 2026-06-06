<?php

namespace App\Services\Provisioning;

use App\Enums\OrderStatus;
use App\Enums\ProvisioningJobType;
use App\Enums\ProvisioningStatus;
use App\Jobs\Provisioning\CreateCloudflareDnsRecordsJob;
use App\Jobs\Provisioning\CreateCloudflareZoneJob;
use App\Jobs\Provisioning\CreateWhmHostingAccountJob;
use App\Jobs\Provisioning\RegisterDomainJob;
use App\Jobs\Provisioning\SendProvisioningCompletedEmailJob;
use App\Jobs\Provisioning\UpdateRegistrarNameserversJob;
use App\Models\Order;

/**
 * Drives the provisioning step chain. Each step job calls advance() on
 * success; advance() finds the first not-yet-completed step (in canonical
 * order) and dispatches its job — or marks the order completed when every
 * required step is done. A failed/manual-review step halts the chain until
 * it is retried, because later steps depend on it.
 */
class ProvisioningOrchestrator
{
    /** Canonical execution order. */
    private const ORDER = [
        'register_domain',
        'create_cloudflare_zone',
        'update_nameservers',
        'create_hosting_account',
        'create_dns_records',
        'send_welcome_email',
    ];

    private const JOBS = [
        'register_domain' => RegisterDomainJob::class,
        'create_cloudflare_zone' => CreateCloudflareZoneJob::class,
        'update_nameservers' => UpdateRegistrarNameserversJob::class,
        'create_hosting_account' => CreateWhmHostingAccountJob::class,
        'create_dns_records' => CreateCloudflareDnsRecordsJob::class,
        'send_welcome_email' => SendProvisioningCompletedEmailJob::class,
    ];

    /** Dispatch the first pending step for an order. */
    public function start(Order $order): void
    {
        $this->advance($order);
    }

    /**
     * Dispatch the next pending step, or complete the order. Stops if the next
     * required step is failed/running/manual-review.
     */
    public function advance(Order $order): void
    {
        $steps = $order->provisioningJobs()->get()->keyBy(fn ($j) => $j->job_type->value);

        foreach (self::ORDER as $type) {
            if (! $steps->has($type)) {
                continue; // this order doesn't require this step
            }

            $step = $steps->get($type);

            if ($step->status === ProvisioningStatus::Completed) {
                continue;
            }

            // First non-completed step: only dispatch if it is pending.
            if ($step->status === ProvisioningStatus::Pending) {
                $this->dispatchStep($type, $order);
            }

            return; // a failed/running step halts the chain
        }

        // Every required step completed → order is done.
        $order->forceFill(['status' => OrderStatus::Completed->value])->save();
    }

    public function jobClassFor(ProvisioningJobType $type): string
    {
        return self::JOBS[$type->value];
    }

    private function dispatchStep(string $type, Order $order): void
    {
        $jobClass = self::JOBS[$type];
        $jobClass::dispatch($order->id);
    }
}
