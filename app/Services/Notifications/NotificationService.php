<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends transactional email and records the outcome in notification_logs so
 * admins can see what was sent and resend failures. Email failures are logged
 * but never throw — provisioning and billing must not be blocked by mail
 * problems (Security & Access §11.10).
 */
class NotificationService
{
    public function send(User $user, Mailable $mailable, string $type): NotificationLog
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'channel' => 'mail',
            'recipient' => $user->email,
            'subject' => $this->subjectOf($mailable),
            'status' => 'pending',
        ]);

        try {
            Mail::to($user->email)->send($mailable);
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'failed_at' => now(), 'error_message' => $e->getMessage()]);
            Log::channel('stack')->error('Email send failed', ['type' => $type, 'error' => $e->getMessage()]);
        }

        return $log;
    }

    private function subjectOf(Mailable $mailable): ?string
    {
        try {
            return $mailable->envelope()->subject;
        } catch (Throwable) {
            return null;
        }
    }
}
