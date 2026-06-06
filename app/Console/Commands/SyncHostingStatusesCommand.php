<?php

namespace App\Console\Commands;

use App\Enums\HostingStatus;
use App\Models\HostingAccount;
use App\Services\Hosting\WhmService;
use Illuminate\Console\Command;
use Throwable;

class SyncHostingStatusesCommand extends Command
{
    protected $signature = 'hosting:sync';

    protected $description = 'Reconcile local hosting account statuses with WHM (detect manual changes).';

    public function handle(WhmService $whm): int
    {
        try {
            $accounts = collect($whm->listAccounts())->keyBy('user');
        } catch (Throwable $e) {
            $this->error("Could not list WHM accounts: {$e->getMessage()}");

            return self::FAILURE;
        }

        $updated = 0;
        HostingAccount::whereNotIn('status', [HostingStatus::Terminated->value, HostingStatus::Failed->value])
            ->each(function (HostingAccount $account) use ($accounts, &$updated) {
                $remote = $accounts->get($account->whm_username);
                if (! $remote) {
                    return;
                }

                $suspended = (int) ($remote['suspended'] ?? 0) === 1;
                $expected = $suspended ? HostingStatus::Suspended : HostingStatus::Active;

                if ($account->status !== $expected) {
                    $account->update(['status' => $expected->value, 'last_synced_at' => now()]);
                    $updated++;
                } else {
                    $account->update(['last_synced_at' => now()]);
                }
            });

        $this->info("Synced hosting statuses ({$updated} change(s)).");

        return self::SUCCESS;
    }
}
