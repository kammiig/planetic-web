<?php

namespace App\Services\Notifications;

use App\Mail\AdminAlertMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Operational alerts to the admin inbox (config billing.admin_email).
 * Best-effort by design: an alert failure is logged and swallowed — it must
 * never break checkout, provisioning or a webhook response.
 */
class AdminNotifier
{
    /**
     * @param  array<string, string>  $details  label => value rows (display-safe only)
     */
    public function alert(string $subject, string $intro, array $details = [], ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        $recipient = (string) config('billing.admin_email');

        if (blank($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new AdminAlertMail($subject, $intro, $details, $actionUrl, $actionLabel));
        } catch (Throwable $e) {
            Log::channel('stack')->error('Admin alert email failed', [
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
