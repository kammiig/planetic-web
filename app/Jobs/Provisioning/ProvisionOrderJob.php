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

        foreach ($this->stepsFor($order) as $jobType) {
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
     * @return array<int, ProvisioningJobType>
     */
    private function stepsFor(Order $order): array
    {
        $needsDomain = $order->items->contains(
            fn ($i) => in_array($i->item_type, [ItemType::WebsitePackage, ItemType::DomainRegistration], true) && filled($i->domain_name)
        );
        $needsHosting = $order->items->contains(
            fn ($i) => in_array($i->item_type, [ItemType::WebsitePackage, ItemType::Hosting], true)
        );

        $steps = [];

        if ($needsDomain) {
            $steps[] = ProvisioningJobType::RegisterDomain;
            $steps[] = ProvisioningJobType::CreateCloudflareZone;
            $steps[] = ProvisioningJobType::UpdateNameservers;
        }

        if ($needsHosting) {
            $steps[] = ProvisioningJobType::CreateHostingAccount;
        }

        // DNS records are only created when we manage the Cloudflare zone.
        if ($needsDomain) {
            $steps[] = ProvisioningJobType::CreateDnsRecords;
        }

        $steps[] = ProvisioningJobType::SendWelcomeEmail;

        return $steps;
    }
}
