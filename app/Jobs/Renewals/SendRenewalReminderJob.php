<?php

namespace App\Jobs\Renewals;

use App\Mail\RenewalReminderMail;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Sends a single renewal reminder, deduplicated by a stable key so the same
 * reminder is never sent twice for the same service/window (Ticket 55).
 */
class SendRenewalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $serviceName,
        public string $renewalDate,
        public ?float $amount,
        public int $daysBefore,
        public string $dedupKey,
    ) {}

    public function handle(NotificationService $notifications): void
    {
        if (NotificationLog::where('type', 'renewal_reminder')->where('metadata->reminder_key', $this->dedupKey)->exists()) {
            return;
        }

        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $log = $notifications->send($user, new RenewalReminderMail(
            $user->name,
            $this->serviceName,
            Carbon::parse($this->renewalDate)->format('j M Y'),
            $this->amount,
            $this->daysBefore,
        ), 'renewal_reminder');

        $log->update(['metadata' => ['reminder_key' => $this->dedupKey]]);
    }
}
