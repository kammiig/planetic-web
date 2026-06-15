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
use Illuminate\Support\Facades\Log;

/**
 * Drives the provisioning step chain. Each step job calls advance() on
 * success; advance() finds the first not-yet-completed step (in canonical
 * order) and dispatches its job — or marks the order completed when every
 * required step is done. A failed/manual-review step halts the chain until
 * it is retried, because later steps depend on it.
 */
class ProvisioningOrchestrator
{
    /**
     * Canonical execution order. DNS records are created right after the zone
     * and BEFORE the cPanel account, so a slow/failed WHM step can never block
     * DNS from being set up (the zone → records → hosting sequence the
     * customer expects). SSL mode is configured inside the zone step.
     */
    private const ORDER = [
        'register_domain',
        'create_cloudflare_zone',
        'create_dns_records',
        'update_nameservers',
        'create_hosting_account',
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
        if ($order->status !== OrderStatus::Completed) {
            $order->forceFill(['status' => OrderStatus::Completed->value])->save();
            Log::channel('stack')->info('Provisioning completed — all steps done.', [
                'order_id' => $order->id,
                'order' => $order->order_number,
            ]);
        }
    }

    public function jobClassFor(ProvisioningJobType $type): string
    {
        return self::JOBS[$type->value];
    }

    /**
     * Dispatch a provisioning job. Runs inline when provisioning.sync is on
     * (default) so the whole chain completes without a queue worker; otherwise
     * pushes onto the queue.
     */
    public function dispatchJob(string $jobClass, int $orderId): void
    {
        if (config('provisioning.sync', true)) {
            $jobClass::dispatchSync($orderId);

            return;
        }

        $jobClass::dispatch($orderId);
    }

    private function dispatchStep(string $type, Order $order): void
    {
        $this->dispatchJob(self::JOBS[$type], $order->id);
    }
}
