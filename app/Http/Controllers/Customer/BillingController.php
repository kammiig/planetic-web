<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\Invoice;
use App\Models\TldPricing;
use App\Models\User;
use App\Services\Billing\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(private readonly StripeService $stripe) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('customer.billing.index', [
            'invoices' => $user->invoices()->latest()->paginate(10),
            'subscriptions' => $user->subscriptions()->with('product')->latest()->get(),
            'payments' => $user->payments()->latest()->take(10)->get(),
            'paymentMethod' => $this->stripe->getDefaultPaymentMethod($user),
            'renewables' => $this->renewables($user),
        ]);
    }

    /**
     * Redirect to the Stripe Billing Portal so the customer can securely update
     * their card and view billing history (card details never touch our server).
     */
    public function paymentMethod(Request $request): RedirectResponse
    {
        $url = $this->stripe->createBillingPortalSession(
            $request->user(),
            route('customer.billing.index'),
        );

        if (! $url) {
            return back()->with('error', 'The payment portal is unavailable right now. Please try again shortly.');
        }

        return redirect()->away($url);
    }

    public function toggleDomainAutoRenew(Request $request, Domain $domain): RedirectResponse
    {
        abort_unless($domain->isOwnedBy($request->user()), 404);

        $domain->update(['auto_renew' => ! $domain->auto_renew]);
        $this->syncSubscriptionAutoRenew($request->user(), 'domain_id', $domain->id, $domain->auto_renew);

        return back()->with('success', 'Auto-renew '.($domain->auto_renew ? 'enabled' : 'disabled').' for '.$domain->domain_name.'.');
    }

    public function toggleHostingAutoRenew(Request $request, HostingAccount $hostingAccount): RedirectResponse
    {
        abort_unless($hostingAccount->isOwnedBy($request->user()), 404);

        $hostingAccount->update(['auto_renew' => ! $hostingAccount->auto_renew]);
        $this->syncSubscriptionAutoRenew($request->user(), 'hosting_account_id', $hostingAccount->id, $hostingAccount->auto_renew);

        $label = $hostingAccount->domain_name ?: ($hostingAccount->hostingPackage?->name ?? 'your hosting');

        return back()->with('success', 'Auto-renew '.($hostingAccount->auto_renew ? 'enabled' : 'disabled').' for '.$label.'.');
    }

    public function showInvoice(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->isOwnedBy($request->user()), 404);

        $invoice->load('order.items');

        return view('customer.billing.invoice', ['invoice' => $invoice]);
    }

    public function downloadInvoice(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->isOwnedBy($request->user()), 404);

        $invoice->load('order.items', 'user');

        // Print-optimised, standalone layout (use the browser's "Save as PDF").
        return view('customer.billing.invoice-print', ['invoice' => $invoice]);
    }

    /**
     * A unified list of renewable services (domains + hosting) with their next
     * renewal date, renewal amount and auto-renew state, for the Billing page.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function renewables(User $user): Collection
    {
        $subs = $user->subscriptions()->get();
        $domainSubs = $subs->whereNotNull('domain_id')->keyBy('domain_id');
        $hostingSubs = $subs->whereNotNull('hosting_account_id')->keyBy('hosting_account_id');

        $rows = collect();

        foreach ($user->domains()->get() as $domain) {
            $tld = TldPricing::forDomain($domain->domain_name);
            $amount = $domainSubs->get($domain->id)?->amount
                ?? $tld?->renew_price ?? $tld?->register_price ?? 12.99;

            $rows->push([
                'type' => 'domain',
                'label' => $domain->domain_name,
                'sub_label' => 'Domain',
                'renewal_date' => $domain->expiry_date,
                'amount' => (float) $amount,
                'cycle' => '/yr',
                'auto_renew' => (bool) $domain->auto_renew,
                'toggle_url' => route('customer.billing.domains.auto-renew', $domain),
            ]);
        }

        foreach ($user->hostingAccounts()->with('hostingPackage.product.prices')->get() as $account) {
            $sub = $hostingSubs->get($account->id);
            $cycle = $sub?->billing_cycle ?? 'monthly';
            $amount = $sub?->amount
                ?? $account->hostingPackage?->product?->priceFor($cycle)?->amount
                ?? $account->hostingPackage?->product?->priceFor('monthly')?->amount
                ?? 0;

            $rows->push([
                'type' => 'hosting',
                'label' => $account->domain_name ?: ($account->hostingPackage?->name ?? 'Hosting account'),
                'sub_label' => $account->hostingPackage?->name ?? 'Hosting',
                'renewal_date' => $account->renewal_date,
                'amount' => (float) $amount,
                'cycle' => $cycle === 'yearly' ? '/yr' : '/mo',
                'auto_renew' => (bool) $account->auto_renew,
                'toggle_url' => route('customer.billing.hosting.auto-renew', $account),
            ]);
        }

        return $rows;
    }

    /** Keep a related subscription's renewal intent in step with the toggle. */
    private function syncSubscriptionAutoRenew(User $user, string $column, int $id, bool $autoRenew): void
    {
        $user->subscriptions()->where($column, $id)->update(['cancel_at_period_end' => ! $autoRenew]);
    }
}
