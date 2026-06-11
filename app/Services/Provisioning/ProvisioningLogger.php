<?php

namespace App\Services\Provisioning;

use App\Enums\OrderStatus;
use App\Enums\ProvisioningJobType;
use App\Mail\ProvisioningIssueMail;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Notifications\AdminNotifier;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Records the lifecycle of each provisioning step into the provisioning_jobs
 * ledger and the application log. Request/response payloads are stored for
 * admin debugging but are never shown to customers (Security & Access §16).
 */
class ProvisioningLogger
{
    /** Get or create the ledger row for a step (idempotent). */
    public function step(Order $order, ProvisioningJobType $type): ProvisioningJob
    {
        return $order->provisioningJobs()->firstOrCreate(
            ['job_type' => $type->value],
            ['user_id' => $order->user_id, 'status' => 'pending', 'max_attempts' => 3],
        );
    }

    /** @param array<string, mixed> $request */
    public function running(ProvisioningJob $job, array $request = []): void
    {
        $job->forceFill(['request_payload' => $this->sanitise($request)])->save();
        $job->markRunning();
    }

    /** @param array<string, mixed> $response */
    public function completed(ProvisioningJob $job, array $response = []): void
    {
        $job->markCompleted($this->sanitise($response));
        Log::channel('stack')->info('Provisioning step completed', [
            'order_id' => $job->order_id,
            'step' => $job->job_type->value,
        ]);
    }

    /** @param array<string, mixed> $response */
    public function failed(ProvisioningJob $job, string $message, array $response = [], bool $manualReview = false): void
    {
        $job->markFailed($message, $this->sanitise($response), $manualReview);

        // Surface the failure on the order so the customer sees a safe
        // "being reviewed" state and admins can act.
        $order = $job->order;
        $firstEscalation = false;

        if ($order && $order->status !== OrderStatus::Completed) {
            $firstEscalation = $order->status !== OrderStatus::ManualReview;
            $order->forceFill(['status' => OrderStatus::ManualReview->value])->save();
        }

        Log::channel('stack')->error('Provisioning step failed', [
            'order_id' => $job->order_id,
            'step' => $job->job_type->value,
            'manual_review' => $manualReview,
            'error' => $message,
        ]);

        // Tell the admin team immediately (failure details stay internal) …
        app(AdminNotifier::class)->alert(
            'Provisioning step failed: '.$job->job_type->value,
            'A provisioning step needs attention. The customer sees a safe "manual review" state; the service record stays visible in their dashboard.',
            array_filter([
                'Order' => $order?->order_number,
                'Customer' => $order?->user?->email,
                'Step' => $job->job_type->value,
                'Attempts' => (string) $job->attempts,
                'Needs manual review' => $manualReview ? 'yes' : 'no (auto-retry eligible)',
                'Error' => $message,
            ]),
            url('/admin/provisioning-jobs'),
            'Open provisioning monitor',
        );

        // … and reassure the customer once (first escalation only, never spam).
        if ($firstEscalation && $order && $order->user) {
            app(NotificationService::class)->send(
                $order->user,
                new ProvisioningIssueMail($order),
                'provisioning_issue',
            );
        }
    }

    /**
     * Strip anything sensitive before persisting a payload (passwords, tokens).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitise(array $payload): array
    {
        $redactKeys = ['password', 'token', 'api_token', 'key', 'secret', 'authorization'];

        array_walk_recursive($payload, function (&$value, $key) use ($redactKeys) {
            if (is_string($key) && in_array(strtolower($key), $redactKeys, true)) {
                $value = '[redacted]';
            }
        });

        return $payload;
    }
}
