<?php

namespace App\Services\Renewals;

use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Subscription;
use Illuminate\Support\Collection;

/**
 * Finds services due for renewal and the customers to remind. Renewal dates
 * live on domains (expiry_date), hosting accounts (renewal_date) and
 * subscriptions (next_renewal_date).
 */
class RenewalService
{
    /**
     * Services whose renewal date is exactly $days from today.
     *
     * @return Collection<int, array{type: string, id: int, user: \App\Models\User, name: string, date: \Illuminate\Support\Carbon, amount: ?float}>
     */
    public function dueInDays(int $days): Collection
    {
        $target = today()->addDays($days)->toDateString();
        $items = collect();

        Domain::with('user')
            ->where('status', 'active')
            ->whereDate('expiry_date', $target)
            ->each(fn (Domain $d) => $items->push([
                'type' => 'domain', 'id' => $d->id, 'user' => $d->user,
                'name' => $d->domain_name.' (domain)', 'date' => $d->expiry_date, 'amount' => null,
            ]));

        HostingAccount::with('user', 'hostingPackage')
            ->where('status', 'active')
            ->whereDate('renewal_date', $target)
            ->each(fn (HostingAccount $h) => $items->push([
                'type' => 'hosting', 'id' => $h->id, 'user' => $h->user,
                'name' => ($h->hostingPackage?->name ?? 'Hosting').' for '.$h->domain_name,
                'date' => $h->renewal_date, 'amount' => null,
            ]));

        Subscription::with('user', 'product')
            ->where('status', 'active')
            ->whereDate('next_renewal_date', $target)
            ->each(fn (Subscription $s) => $items->push([
                'type' => 'subscription', 'id' => $s->id, 'user' => $s->user,
                'name' => $s->product?->name ?? 'Subscription', 'date' => $s->next_renewal_date, 'amount' => (float) $s->amount,
            ]));

        return $items->filter(fn ($i) => $i['user'] !== null)->values();
    }

    /**
     * Hosting accounts that are still active but overdue beyond the grace
     * period (renewal date + grace days has passed) and should be suspended.
     *
     * @return Collection<int, HostingAccount>
     */
    public function overdueHostingPastGrace(): Collection
    {
        $cutoff = today()->subDays((int) config('billing.grace_period_days', 7));

        return HostingAccount::with('user')
            ->where('status', 'active')
            ->whereNotNull('renewal_date')
            ->whereDate('renewal_date', '<', $cutoff->toDateString())
            ->get();
    }
}
