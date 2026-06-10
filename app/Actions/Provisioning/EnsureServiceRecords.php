<?php

namespace App\Actions\Provisioning;

use App\Enums\DomainStatus;
use App\Enums\HostingStatus;
use App\Enums\ItemType;
use App\Enums\WebsiteProjectStatus;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Services\Hosting\CpanelPackageMapper;
use App\Support\DomainName;
use Illuminate\Support\Facades\Log;

/**
 * Creates the customer-visible "skeleton" service records the moment a payment
 * is confirmed — a domain row, a hosting account row and/or a website project —
 * each in a Pending state. This guarantees the Domains / Hosting / Website
 * Projects tabs are never empty after a successful purchase, even before the
 * external provisioning APIs (NameSilo, WHM, Cloudflare) have run or if they
 * fail. The provisioning step jobs later adopt these rows and flip them to
 * Active (or Failed / Manual review).
 *
 * Fully idempotent: existing rows are reused, never duplicated, so it is safe
 * to call from the webhook flow and again from `orders:provision`.
 */
class EnsureServiceRecords
{
    public function __construct(private readonly CpanelPackageMapper $packageMapper) {}

    public function handle(Order $order): void
    {
        $order->loadMissing('items');

        // Order matters: the domain row is created first so the hosting account
        // and website project can link to it.
        $this->ensureDomain($order);
        $this->ensureHosting($order);
        $this->ensureWebsiteProject($order);
    }

    private function ensureDomain(Order $order): void
    {
        $domainName = $this->domainNameFor($order);

        if (blank($domainName) || $order->domain()->exists()) {
            return;
        }

        // A row may already exist globally (domain_name is unique) from a prior
        // attempt — adopt it for this order rather than failing on the unique key.
        $existing = Domain::where('domain_name', $domainName)->first();
        if ($existing) {
            if (blank($existing->order_id)) {
                $existing->forceFill(['order_id' => $order->id, 'user_id' => $order->user_id])->save();
            }

            return;
        }

        $parsed = DomainName::parse($domainName);

        Domain::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'domain_name' => $domainName,
            'sld' => $parsed->sld,
            'tld' => $parsed->tld,
            'registrar' => config('domain.default_registrar'),
            'status' => DomainStatus::RegistrationPending->value,
            'registration_date' => now()->toDateString(),
            'auto_renew' => config('domain.defaults.auto_renew', true),
            'whois_privacy' => config('domain.defaults.whois_privacy', true),
            'registrar_lock' => config('domain.defaults.registrar_lock', true),
        ]);

        Log::channel('stack')->info('Provisioning: pending domain record created', [
            'order' => $order->order_number,
            'domain' => $domainName,
        ]);
    }

    private function ensureHosting(Order $order): void
    {
        if (! $this->needsHosting($order) || $order->hostingAccount()->exists()) {
            return;
        }

        $domainName = $this->hostingDomainName($order);
        $package = $this->resolveHostingPackage($order);

        // hosting_accounts.hosting_package_id is NOT NULL and whm_username is
        // unique+required, so we need both up front. If either is missing the
        // skeleton is skipped (logged) — the WHM step will surface the problem.
        if (blank($domainName) || ! $package) {
            Log::channel('stack')->warning('Provisioning: could not create pending hosting record (missing domain or package).', [
                'order' => $order->order_number,
                'has_domain' => filled($domainName),
                'has_package' => (bool) $package,
            ]);

            return;
        }

        HostingAccount::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'domain_id' => $order->domain()->value('id'),
            'hosting_package_id' => $package->id,
            'domain_name' => $domainName,
            'whm_username' => $this->packageMapper->generateUsername($domainName),
            'server_hostname' => config('whm.server_hostname'),
            'status' => HostingStatus::Pending->value,
            'disk_limit_mb' => $package->disk_limit_mb,
            'bandwidth_limit_mb' => $package->bandwidth_limit_mb,
            'renewal_date' => now()->addYear()->toDateString(),
        ]);

        Log::channel('stack')->info('Provisioning: pending hosting record created', [
            'order' => $order->order_number,
            'domain' => $domainName,
            'package' => $package->whm_package_name,
        ]);
    }

    private function ensureWebsiteProject(Order $order): void
    {
        if (! $order->containsWebsitePackage() || $order->websiteProject()->exists()) {
            return;
        }

        $project = $order->websiteProject()->create([
            'user_id' => $order->user_id,
            'domain_id' => $order->domain()->value('id'),
            'project_number' => 'TMP-'.uniqid(),
            'status' => WebsiteProjectStatus::InformationRequired->value,
            'business_name' => $order->user->company_name,
        ]);

        $project->update(['project_number' => 'PRJ-'.(10000 + $project->id)]);

        Log::channel('stack')->info('Provisioning: website project created', [
            'order' => $order->order_number,
            'project' => $project->project_number,
        ]);
    }

    /** The domain to register for this order (website package / domain registration only). */
    private function domainNameFor(Order $order): ?string
    {
        return $order->items->first(fn ($i) => in_array($i->item_type, [
            ItemType::WebsitePackage,
            ItemType::DomainRegistration,
        ], true) && filled($i->domain_name))?->domain_name;
    }

    private function needsHosting(Order $order): bool
    {
        return $order->items->contains(
            fn ($i) => in_array($i->item_type, [ItemType::WebsitePackage, ItemType::Hosting], true)
        );
    }

    /** Hosting may reuse the registered domain, a hosting line's own domain, or any order domain. */
    private function hostingDomainName(Order $order): ?string
    {
        return $this->domainNameFor($order)
            ?? $order->items->firstWhere('item_type', ItemType::Hosting)?->domain_name
            ?? $order->primaryDomainName();
    }

    private function resolveHostingPackage(Order $order): ?HostingPackage
    {
        $hostingItem = $order->items->firstWhere('item_type', ItemType::Hosting);

        if ($hostingItem && $hostingItem->product) {
            $package = $hostingItem->product->hostingPackage()->first();
            if ($package) {
                return $package;
            }
        }

        return HostingPackage::where('whm_package_name', config('hosting.default_package'))->first()
            ?? HostingPackage::where('is_active', true)->orderBy('id')->first();
    }
}
