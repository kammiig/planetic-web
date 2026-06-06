<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('customer.billing.index', [
            'invoices' => $user->invoices()->latest()->paginate(10),
            'subscriptions' => $user->subscriptions()->with('product')->latest()->get(),
            'payments' => $user->payments()->latest()->take(10)->get(),
        ]);
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
}
