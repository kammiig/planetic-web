<?php

namespace App\Jobs\Provisioning;

use App\Enums\HostingStatus;
use App\Enums\ItemType;
use App\Enums\ProvisioningJobType;
use App\Exceptions\ProvisioningException;
use App\Exceptions\WhmException;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Hosting\CpanelPackageMapper;
use App\Services\Hosting\WhmService;
use Illuminate\Support\Facades\Log;

/**
 * Creates the cPanel account via WHM (Ticket 31). Idempotent on the order:
 * a duplicate account is never created. It adopts the pending hosting record
 * created at payment time (so the dashboard already shows the account) and
 * flips it to Active on success. If WHM fails, the record is kept and marked
 * Failed / Manual review — the customer's hosting never silently disappears.
 * Domain-already-exists collisions are routed to manual review by WhmService.
 */
class CreateWhmHostingAccountJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::CreateHostingAccount;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        $account = $order->hostingAccount()->first();

        // Idempotency — the account has already been created on WHM.
        if ($account && $account->created_on_whm_at && $account->status === HostingStatus::Active) {
            return ['skipped' => true, 'reason' => 'account_exists'];
        }

        $domainName = $account?->domain_name
            ?? $order->domain()->value('domain_name')
            ?? $this->existingDomainName($order);

        // Defence in depth — this step is no longer scheduled for orders
        // without a domain, but if it ever runs anyway the hosting stays
        // visible in "Awaiting domain" instead of becoming a fake failure.
        if (blank($domainName)) {
            $account?->update(['status' => HostingStatus::AwaitingDomain->value]);

            return ['skipped' => true, 'reason' => 'awaiting_domain'];
        }

        $package = $account?->hostingPackage ?? $this->resolvePackage($order);

        if (! $package) {
            throw new ProvisioningException('No hosting package is configured for this order.', manualReview: true);
        }

        $mapper = app(CpanelPackageMapper::class);
        // Reuse the username generated for the pending record so the customer
        // sees a stable username from the moment of purchase.
        $username = $account?->whm_username ?: $mapper->generateUsername($domainName);
        $plan = $mapper->whmPackageFor($package);

        // Safe test mode: simulate a created account without touching WHM.
        if (config('provisioning.dry_run', false)) {
            $this->persistActiveAccount($order, $account, $domainName, $username, $package, config('whm.server_ip') ?: '127.0.0.1');

            return ['simulated' => true, 'username' => $username, 'package' => $plan];
        }

        $whm = app(WhmService::class);

        // Idempotent retry: if a previous attempt actually created the account
        // on WHM (e.g. it timed out client-side), adopt it instead of trying to
        // create a duplicate.
        if ($existing = $whm->findAccountByDomain($domainName)) {
            Log::channel('stack')->info('WHM account already present — adopting it.', [
                'order' => $order->order_number,
                'domain' => $domainName,
            ]);

            $serverIp = ($existing['ip'] ?? null) ?: config('whm.server_ip');
            $this->persistActiveAccount($order, $account, $domainName, $existing['user'] ?? $username, $package, $serverIp);

            return ['username' => $existing['user'] ?? $username, 'ip' => $serverIp, 'package' => $plan, 'adopted' => true];
        }

        try {
            $result = $whm->createAccount([
                'username' => $username,
                'domain' => $domainName,
                'contactemail' => $order->user->email,
                'plan' => $plan,
                'password' => $mapper->generatePassword(),
            ]);
        } catch (WhmException $e) {
            // Payment succeeded but account creation failed → keep a visible
            // record marked failed/manual review, then propagate so the step
            // is recorded as failed and the order goes to manual review.
            $this->markFailedAccount($order, $account, $domainName, $username, $package, $e->manualReview);

            throw $e;
        }

        $serverIp = $result['ip'] ?: config('whm.server_ip');

        Log::channel('stack')->info('WHM account created.', [
            'order' => $order->order_number,
            'username' => $username,
            'package' => $plan,
        ]);

        $this->persistActiveAccount($order, $account, $domainName, $username, $package, $serverIp);

        // Note: the generated cPanel password is intentionally not stored in
        // plain text. Customers access cPanel via the dashboard link / reset.
        return ['username' => $username, 'ip' => $serverIp, 'package' => $plan];
    }

    /** Create or adopt the hosting record and mark it Active. */
    private function persistActiveAccount(Order $order, ?HostingAccount $account, string $domainName, string $username, ?HostingPackage $package, ?string $serverIp): void
    {
        $attributes = [
            'domain_id' => $order->domain()->value('id'),
            'hosting_package_id' => $package?->id,
            'domain_name' => $domainName,
            'whm_username' => $username,
            'server_hostname' => config('whm.server_hostname'),
            'server_ip' => $serverIp,
            'cpanel_url' => config('whm.cpanel_login_url') ?: ($serverIp ? "https://{$serverIp}:2083" : null),
            'status' => HostingStatus::Active->value,
            'disk_limit_mb' => $package?->disk_limit_mb,
            'bandwidth_limit_mb' => $package?->bandwidth_limit_mb,
            'created_on_whm_at' => now(),
            'renewal_date' => $account?->renewal_date?->toDateString() ?? now()->addYear()->toDateString(),
            'last_synced_at' => now(),
        ];

        if ($account) {
            $account->update($attributes);

            return;
        }

        HostingAccount::create(array_merge($attributes, [
            'user_id' => $order->user_id,
            'order_id' => $order->id,
        ]));
    }

    /** Ensure a visible hosting record exists, marked failed / manual review. */
    private function markFailedAccount(Order $order, ?HostingAccount $account, string $domainName, string $username, ?HostingPackage $package, bool $manualReview): void
    {
        $status = $manualReview ? HostingStatus::ManualReview->value : HostingStatus::Failed->value;

        if ($account) {
            $account->update(['status' => $status]);

            return;
        }

        HostingAccount::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'domain_id' => $order->domain()->value('id'),
            'hosting_package_id' => $package?->id,
            'domain_name' => $domainName,
            'whm_username' => $username,
            'server_hostname' => config('whm.server_hostname'),
            'status' => $status,
            'disk_limit_mb' => $package?->disk_limit_mb,
            'bandwidth_limit_mb' => $package?->bandwidth_limit_mb,
            'renewal_date' => now()->addYear()->toDateString(),
        ]);
    }

    private function existingDomainName(Order $order): ?string
    {
        return $order->items->firstWhere('item_type', ItemType::Hosting)?->domain_name
            ?? $order->primaryDomainName();
    }

    private function resolvePackage(Order $order): ?HostingPackage
    {
        $hostingItem = $order->items->firstWhere('item_type', ItemType::Hosting);

        if ($hostingItem && $hostingItem->product) {
            $package = $hostingItem->product->hostingPackage()->first();
            if ($package) {
                return $package;
            }
        }

        // Website package (or fallback) → the default WHM package.
        return HostingPackage::where('whm_package_name', config('hosting.default_package'))->first()
            ?? HostingPackage::where('is_active', true)->orderBy('id')->first();
    }
}
