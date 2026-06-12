<?php

namespace App\Jobs\Provisioning;

use App\Enums\HostingStatus;
use App\Enums\ProvisioningJobType;
use App\Mail\DomainNeededMail;
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
        $hosting = $order->hostingAccount()->first();

        // "Decide my domain later" orders get an action-required email instead
        // of "your services are ready" — the ready email follows once the
        // domain is provided and provisioning has actually run.
        $awaitingDomain = ($hosting && $hosting->status === HostingStatus::AwaitingDomain)
            || ($order->needsHosting() && blank($order->domainChoice()['domain']));

        $log = $awaitingDomain
            ? app(NotificationService::class)->send($order->user, new DomainNeededMail($order), 'domain_needed')
            : app(NotificationService::class)->send(
                $order->user,
                new ProvisioningCompletedMail($order, $order->domain()->first(), $hosting),
                'provisioning_completed',
            );

        return ['notification_log_id' => $log->id, 'status' => $log->status, 'variant' => $awaitingDomain ? 'domain_needed' : 'completed'];
    }
}
