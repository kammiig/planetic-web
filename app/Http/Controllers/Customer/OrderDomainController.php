<?php

namespace App\Http\Controllers\Customer;

use App\Actions\Domains\CheckDomainAvailability;
use App\Actions\Provisioning\EnsureServiceRecords;
use App\Enums\HostingStatus;
use App\Enums\ItemType;
use App\Enums\ProvisioningJobType;
use App\Http\Controllers\Controller;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Models\Order;
use App\Services\Hosting\CpanelPackageMapper;
use App\Support\DomainName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Lets a customer who chose "I'll decide my domain later" at checkout provide
 * their domain afterwards. Saving it updates the order + service records and
 * immediately re-runs provisioning (registration, Cloudflare, cPanel account),
 * so the dashboard goes from "Waiting for domain" to live services.
 */
class OrderDomainController extends Controller
{
    public function store(Request $request, Order $order, CpanelPackageMapper $mapper): RedirectResponse
    {
        abort_unless($order->isOwnedBy($request->user()), 404);
        abort_unless($order->isPaid(), 404);

        $validated = $request->validate([
            'domain_source' => ['required', 'in:new,existing'],
            'domain_name' => ['required', 'string', 'max:253'],
        ], [
            'domain_name.required' => 'Please enter a domain name.',
        ]);

        $order->loadMissing('items');

        // Only orders still waiting on a domain can be completed this way.
        if (filled($order->domainChoice()['domain'])) {
            return back()->with('info', 'This order already has a domain attached.');
        }

        $domain = DomainName::normalise($validated['domain_name']);

        if (! DomainName::isValid($domain)) {
            throw ValidationException::withMessages([
                'domain_name' => 'Please enter a valid domain name, e.g. yourbusiness.com.',
            ]);
        }

        if ($validated['domain_source'] === 'new') {
            $availability = app(CheckDomainAvailability::class)->handle($domain);

            if (! ($availability['available'] ?? false)) {
                throw ValidationException::withMessages([
                    'domain_name' => $domain.' is not available to register. Try another name, or choose "I already own this domain".',
                ]);
            }
        }

        // 1. Stamp the choice onto the order items (provisioning reads these).
        foreach ($order->items as $item) {
            if (! in_array($item->item_type, [ItemType::WebsitePackage, ItemType::Hosting], true)) {
                continue;
            }

            $item->update([
                'domain_name' => $domain,
                'name' => $item->item_type === ItemType::WebsitePackage
                    ? 'Complete Bespoke Website (with '.$domain.')'
                    : $item->name,
                'metadata' => array_merge($item->metadata ?? [], ['domain_source' => $validated['domain_source']]),
            ]);
        }

        // 2. Wake the parked hosting record up.
        $hosting = $order->hostingAccount()->first();
        if ($hosting && $hosting->status === HostingStatus::AwaitingDomain) {
            $hosting->update([
                'domain_name' => $domain,
                'whm_username' => $hosting->whm_username ?: $mapper->generateUsername($domain),
                'status' => HostingStatus::Pending->value,
            ]);
        }

        // 3. The "waiting for your domain" email step already ran — reset it so
        //    the customer gets the real "services ready" email after this run.
        $order->provisioningJobs()
            ->where('job_type', ProvisioningJobType::SendWelcomeEmail->value)
            ->delete();

        // 4. Create the domain record and run the remaining provisioning steps.
        try {
            app(EnsureServiceRecords::class)->handle($order->fresh('items'));
            ProvisionOrderJob::dispatchSync($order->id);
        } catch (Throwable $e) {
            Log::channel('stack')->error('Provisioning after late domain choice failed.', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', "Domain saved — we're setting up {$domain} now. This page will update as each step completes.");
    }
}
