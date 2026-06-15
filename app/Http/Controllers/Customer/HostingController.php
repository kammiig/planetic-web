<?php

namespace App\Http\Controllers\Customer;

use App\Enums\HostingStatus;
use App\Http\Controllers\Controller;
use App\Models\HostingAccount;
use App\Services\Hosting\WhmService;
use App\Support\Secrets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class HostingController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.hosting.index', [
            'accounts' => $request->user()->hostingAccounts()->with(['hostingPackage', 'order', 'domain.cloudflareZone'])->latest()->paginate(15),
        ]);
    }

    public function show(Request $request, HostingAccount $hostingAccount): View
    {
        abort_unless($hostingAccount->isOwnedBy($request->user()), 404);

        $hostingAccount->load('hostingPackage', 'domain.cloudflareZone', 'order.items');

        return view('customer.hosting.show', ['account' => $hostingAccount]);
    }

    /**
     * One-click cPanel login. Creates a short-lived WHM session for the owner's
     * account and redirects the browser to the one-time URL — no password is
     * ever shown or required. Owner-only; safe fallback on failure.
     */
    public function cpanelSso(Request $request, HostingAccount $hostingAccount, WhmService $whm): RedirectResponse
    {
        abort_unless($hostingAccount->isOwnedBy($request->user()), 404);

        if ($hostingAccount->status !== HostingStatus::Active || blank($hostingAccount->whm_username)) {
            return back()->with('error', 'cPanel is not ready yet for this account. Please try again once setup is complete.');
        }

        try {
            $url = $whm->createUserSession($hostingAccount->whm_username);
        } catch (Throwable $e) {
            Log::channel('stack')->error('cPanel SSO failed.', [
                'hosting_account' => $hostingAccount->id,
                'error' => Secrets::redact($e->getMessage()),
            ]);

            return back()->with('error', 'We could not open cPanel automatically right now. Please try again in a moment, or contact support.');
        }

        // Redirect straight to the one-time session URL (token only, no secrets).
        return redirect()->away($url);
    }
}
