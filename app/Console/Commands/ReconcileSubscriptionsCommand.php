<?php

namespace App\Console\Commands;

use App\Enums\HostingStatus;
use App\Enums\SubscriptionStatus;
use App\Models\HostingAccount;
use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Repairs renewal subscriptions so Billing only ever shows live services:
 *  - links legacy hosting subscriptions to their hosting account, and
 *  - cancels "orphaned" subscriptions whose service never became active
 *    (e.g. a free order where domain registration failed).
 *
 * Going forward subscriptions are only created on provisioning success, so this
 * is mainly a one-off cleanup for orders created before that change.
 */
class ReconcileSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:reconcile {--dry-run : Report only, make no changes}';

    protected $description = 'Cancel renewal subscriptions whose service is not active, and link hosting subscriptions.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $keep = [HostingStatus::Active->value, HostingStatus::Suspended->value];
        $linked = 0;
        $cancelled = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->chunkById(200, function ($subs) use (&$linked, &$cancelled, $dry, $keep) {
                foreach ($subs as $sub) {
                    $account = $sub->hosting_account_id
                        ? HostingAccount::find($sub->hosting_account_id)
                        : HostingAccount::where('user_id', $sub->user_id)
                            ->whereIn('status', [HostingStatus::Active->value, HostingStatus::Suspended->value])
                            ->whereHas('hostingPackage', fn ($q) => $q->where('product_id', $sub->product_id))
                            ->first();

                    $alive = $account && in_array($account->status->value, $keep, true);

                    if ($alive) {
                        if (! $sub->hosting_account_id) {
                            if (! $dry) {
                                $sub->update(['hosting_account_id' => $account->id]);
                            }
                            $linked++;
                        }

                        continue;
                    }

                    if (! $dry) {
                        $sub->update([
                            'status' => SubscriptionStatus::Cancelled->value,
                            'cancelled_at' => now(),
                        ]);
                    }
                    $cancelled++;
                }
            });

        $this->info(($dry ? '[dry-run] ' : '')."Linked {$linked} subscription(s); cancelled {$cancelled} orphaned subscription(s).");

        return self::SUCCESS;
    }
}
