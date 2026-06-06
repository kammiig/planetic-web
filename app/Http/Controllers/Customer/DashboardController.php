<?php

namespace App\Http\Controllers\Customer;

use App\Enums\DomainStatus;
use App\Enums\HostingStatus;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $nextRenewal = collect([
            $user->domains()->whereNotNull('expiry_date')->min('expiry_date'),
            $user->hostingAccounts()->whereNotNull('renewal_date')->min('renewal_date'),
            $user->subscriptions()->whereNotNull('next_renewal_date')->min('next_renewal_date'),
        ])->filter()->sort()->first();

        return view('customer.dashboard', [
            'domainsCount' => $user->domains()->where('status', DomainStatus::Active->value)->count(),
            'hostingCount' => $user->hostingAccounts()->where('status', HostingStatus::Active->value)->count(),
            'openInvoicesCount' => $user->invoices()->whereIn('status', [InvoiceStatus::Open->value, InvoiceStatus::Failed->value])->count(),
            'nextRenewal' => $nextRenewal,
            'project' => $user->websiteProjects()->latest()->first(),
            'openTicketsCount' => $user->supportTickets()->whereNotIn('status', ['resolved', 'closed'])->count(),
            'recentOrders' => $user->orders()->latest()->take(3)->get(),
            'inProgressOrders' => $user->orders()->whereIn('status', ['provisioning', 'manual_review', 'pending'])->latest()->get(),
        ]);
    }
}
