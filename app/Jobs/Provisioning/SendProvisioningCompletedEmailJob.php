<?php

namespace App\Jobs\Provisioning;

use App\Enums\ProvisioningJobType;
use App\Mail\ProvisioningCompletedMail;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Notifications\NotificationService;

/**
 * Final provisioning step — emails the customer that their services are ready
 * and the order is marked done. Email failures are logged but never block
 * provisioning (Security & Access §11.10).
 */
class SendProvisioningCompletedEmailJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::SendWelcomeEmail;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        $log = app(NotificationService::class)->send(
            $order->user,
            new ProvisioningCompletedMail(
                $order,
                $order->domain()->first(),
                $order->hostingAccount()->first(),
            ),
            'provisioning_completed',
        );

        return ['notification_log_id' => $log->id, 'status' => $log->status];
    }
}
