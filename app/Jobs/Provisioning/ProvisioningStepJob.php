<?php

namespace App\Jobs\Provisioning;

use App\Enums\ProvisioningJobType;
use App\Enums\ProvisioningStatus;
use App\Exceptions\CloudflareException;
use App\Exceptions\ProvisioningException;
use App\Exceptions\RegistrarException;
use App\Exceptions\WhmException;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Provisioning\ProvisioningLogger;
use App\Services\Provisioning\ProvisioningOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Base class for every provisioning step. Standardises the lifecycle:
 *  - only runs for verified-paid orders;
 *  - skips (and advances) if the step is already completed (idempotent);
 *  - logs request/response to the provisioning_jobs ledger;
 *  - on success advances the chain; on failure marks the step failed (or
 *    manual review) and halts — it never auto-retries blindly, avoiding
 *    duplicate external resources.
 */
abstract class ProvisioningStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retries are controlled explicitly (admin / scheduler), not by the queue. */
    public int $tries = 1;

    public function __construct(public int $orderId) {}

    abstract protected function type(): ProvisioningJobType;

    /**
     * Perform the external work. Return a response payload to log. Throw a
     * RegistrarException / WhmException / CloudflareException / ProvisioningException
     * to mark the step failed.
     *
     * @return array<string, mixed>
     */
    abstract protected function perform(Order $order, ProvisioningJob $step): array;

    /** @return array<string, mixed> */
    protected function requestContext(Order $order): array
    {
        return ['order_number' => $order->order_number];
    }

    public function handle(ProvisioningOrchestrator $orchestrator, ProvisioningLogger $logger): void
    {
        $order = Order::with('items')->find($this->orderId);

        if (! $order || ! $order->isPaid()) {
            return;
        }

        $step = $logger->step($order, $this->type());

        // Already done — move the chain forward.
        if ($step->status === ProvisioningStatus::Completed) {
            $orchestrator->advance($order);

            return;
        }

        $logger->running($step, $this->requestContext($order));

        try {
            $response = $this->perform($order, $step);
            $logger->completed($step, $response);
            $orchestrator->advance($order);
        } catch (WhmException $e) {
            $logger->failed($step, $e->getMessage(), (array) $e->context, $e->manualReview);
        } catch (ProvisioningException $e) {
            $context = is_array($e->context) ? $e->context : (filled($e->context) ? ['detail' => $e->context] : []);
            $logger->failed($step, $e->getMessage(), $context, $e->manualReview);
        } catch (CloudflareException|RegistrarException $e) {
            $logger->failed($step, $e->getMessage());
        } catch (Throwable $e) {
            $logger->failed($step, $e->getMessage());
        }
    }
}
