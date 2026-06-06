<?php

namespace App\Services\Renewals;

use App\Enums\HostingStatus;
use App\Exceptions\WhmException;
use App\Models\HostingAccount;
use App\Services\Hosting\WhmService;
use Illuminate\Support\Facades\Log;

/**
 * Suspends and unsuspends hosting accounts via WHM, keeping the local record
 * in sync. Failures are logged and surfaced (never silently swallowed).
 */
class SuspensionService
{
    public function __construct(private readonly WhmService $whm) {}

    public function suspend(HostingAccount $account, string $reason = 'Payment overdue'): bool
    {
        try {
            $this->whm->suspendAccount($account->whm_username, $reason);
        } catch (WhmException $e) {
            Log::channel('stack')->error('Hosting suspension failed', [
                'account' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $account->forceFill([
            'status' => HostingStatus::Suspended->value,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ])->save();

        return true;
    }

    public function unsuspend(HostingAccount $account): bool
    {
        try {
            $this->whm->unsuspendAccount($account->whm_username);
        } catch (WhmException $e) {
            Log::channel('stack')->error('Hosting unsuspension failed', [
                'account' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $account->forceFill([
            'status' => HostingStatus::Active->value,
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();

        return true;
    }
}
