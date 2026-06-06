<?php

namespace App\Jobs\Provisioning;

use App\Enums\HostingStatus;
use App\Enums\ItemType;
use App\Enums\ProvisioningJobType;
use App\Exceptions\ProvisioningException;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Hosting\CpanelPackageMapper;
use App\Services\Hosting\WhmService;

/**
 * Creates the cPanel account via WHM (Ticket 31). Idempotent on the order:
 * a duplicate account is never created. Domain-already-exists collisions are
 * routed to manual review by WhmService.
 */
class CreateWhmHostingAccountJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::CreateHostingAccount;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        // Idempotency — one hosting account per order.
        if ($order->hostingAccount()->exists()) {
            return ['skipped' => true, 'reason' => 'account_exists'];
        }

        $domain = $order->domain()->first();
        $domainName = $domain?->domain_name ?? $this->existingDomainName($order);

        if (blank($domainName)) {
            throw new ProvisioningException('Hosting requires a domain name.');
        }

        $package = $this->resolvePackage($order);
        $mapper = app(CpanelPackageMapper::class);

        $username = $mapper->generateUsername($domainName);
        $password = $mapper->generatePassword();
        $plan = $mapper->whmPackageFor($package);

        $result = app(WhmService::class)->createAccount([
            'username' => $username,
            'domain' => $domainName,
            'contactemail' => $order->user->email,
            'plan' => $plan,
            'password' => $password,
        ]);

        $serverIp = $result['ip'] ?: config('whm.server_ip');

        HostingAccount::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'domain_id' => $domain?->id,
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
            'renewal_date' => now()->addYear()->toDateString(),
            'last_synced_at' => now(),
        ]);

        // Note: the generated cPanel password is intentionally not stored in
        // plain text. Customers access cPanel via the dashboard link / reset.
        return ['username' => $username, 'ip' => $serverIp, 'package' => $plan];
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
