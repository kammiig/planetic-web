<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\DNS\CloudflareStatusSync;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.domains.index', [
            'domains' => $request->user()->domains()->with('order.items', 'cloudflareZone')->latest()->paginate(15),
        ]);
    }

    public function show(Request $request, Domain $domain, CloudflareStatusSync $cloudflareSync): View
    {
        // Ownership: 404 hides existence for non-owners (Security §11.14).
        $this->ownedOrFail($request, $domain);

        $domain->load('cloudflareZone');

        // Pull the latest Cloudflare status when the zone is still pending, so
        // the dashboard reflects nameserver verification without waiting for cron.
        $cloudflareSync->refreshIfStale($domain->cloudflareZone);

        return view('customer.domains.show', ['domain' => $domain->fresh('cloudflareZone')]);
    }

    public function dns(Request $request, Domain $domain): View
    {
        $this->ownedOrFail($request, $domain);

        $domain->load('dnsRecords');

        return view('customer.domains.dns', ['domain' => $domain]);
    }

    private function ownedOrFail(Request $request, Domain $domain): void
    {
        abort_unless($domain->isOwnedBy($request->user()), 404);
    }
}
