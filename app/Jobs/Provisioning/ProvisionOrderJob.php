<?php

namespace App\Jobs\Provisioning;

use App\Enums\ItemType;
use App\Enums\ProvisioningJobType;
use App\Enums\ProvisioningStatus;
use App\Models\Order;
use App\Services\Provisioning\ProvisioningOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Entry point of the provisioning pipeline. Builds the idempotent
 * provisioning_jobs ledger (one row per required step) and starts the
 * orchestrated chain. Only ever runs for verified-paid orders.
 */
class ProvisionOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $orderId) {}

    public function handle(ProvisioningOrchestrator $orchestrator): void
    {
        $order = Order::with('items')->find($this->orderId);

        if (! $order || ! $order->isPaid()) {
            return;
        }

        $steps = $this->stepsFor($order);

        Log::channel('stack')->info('Provisioning started.', [
            'order' => $order->order_number,
            'steps' => array_map(fn ($s) => $s->value, $steps),
        ]);

        foreach ($steps as $jobType) {
            $order->provisioningJobs()->firstOrCreate(
                ['job_type' => $jobType->value],
                [
                    'user_id' => $order->user_id,
                    'status' => ProvisioningStatus::Pending->value,
                    'max_attempts' => 3,
                ],
            );
        }

        $orchestrator->start($order);
    }

    /**
     * Build the step list from what the order actually needs and the
     * customer's domain choice. Steps that cannot succeed are never scheduled,
     * so a predictable gap (e.g. "domain to be provided later") can never
     * surface as a fake failure or manual review.
     *
     * @return array<int, ProvisioningJobType>
     */
    private function stepsFor(Order $order): array
    {
        $choice = $order->domainChoice();
        $hasDomain = filled($choice['domain']);

        // 'existing' = the customer already owns the domain at another
        // registrar: we never register it or touch its nameservers, but we DO
        // create the Cloudflare zone + records and the cPanel account for it.
        $registerViaUs = $hasDomain && $choice['source'] !== 'existing';

        $needsHosting = $order->needsHosting();

        // We only manage a Cloudflare zone / DNS when the order also includes
        // hosting (a bundle or the website package) — there is a server to point
        // the records at. A domain-only registration is just registered, so it
        // never gets stuck waiting on DNS that has nowhere to resolve.
        $manageDns = $hasDomain && $needsHosting;

        $steps = [];

        if ($registerViaUs) {
            $steps[] = ProvisioningJobType::RegisterDomain;
        }

        if ($manageDns) {
            $steps[] = ProvisioningJobType::CreateCloudflareZone;

            // Nameservers can only be switched automatically at OUR registrar;
            // for an external domain the customer points them (shown in the
            // dashboard with the Cloudflare nameservers to use).
            if ($registerViaUs) {
                $steps[] = ProvisioningJobType::UpdateNameservers;
            }
        }

        // WHM cannot create an account without a domain. With "decide later"
        // the visible hosting record stays in Awaiting Domain until the
        // customer provides one — no step is scheduled, nothing can fail.
        if ($needsHosting && $hasDomain) {
            $steps[] = ProvisioningJobType::CreateHostingAccount;
        }

        // DNS records are only created when we manage the Cloudflare zone.
        if ($manageDns) {
            $steps[] = ProvisioningJobType::CreateDnsRecords;
        }

        $steps[] = ProvisioningJobType::SendWelcomeEmail;

        return $steps;
    }
}
