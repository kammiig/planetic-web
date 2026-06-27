@extends('layouts.customer')

@section('title', 'Billing')
@section('page-title', 'Billing')

@section('content')
    {{-- Payment method --}}
    <section class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <h2 class="text-lg font-bold">Payment method</h2>
            @if ($paymentMethod)
                <div class="mt-3 flex flex-wrap items-center gap-4">
                    <span class="flex h-11 w-16 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-slate-800 to-slate-600 text-white shadow-sm" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">
                            <span class="capitalize">{{ $paymentMethod['brand'] }}</span>
                            <span class="text-slate-400">•••• {{ $paymentMethod['last4'] }}</span>
                        </p>
                        <p class="text-sm text-slate-500">Expires {{ str_pad((string) $paymentMethod['exp_month'], 2, '0', STR_PAD_LEFT) }}/{{ $paymentMethod['exp_year'] }}</p>
                    </div>
                </div>
            @else
                <p class="mt-3 text-sm text-slate-500">No saved card yet. Add one for quick, secure renewals.</p>
            @endif
            <a href="{{ route('customer.billing.payment-method') }}" class="btn-secondary btn-sm mt-4">
                {{ $paymentMethod ? 'Update payment method' : 'Add payment method' }}
            </a>
            <p class="mt-3 text-xs text-slate-400">Your card is stored securely by Stripe. We never see or store your full card number.</p>
        </div>
    </section>

    {{-- Auto-renew controls --}}
    @if ($renewables->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-bold">Auto-renew</h2>
            <p class="text-sm text-slate-500">Choose which services renew automatically before they expire.</p>
            <div class="table-wrap mt-3">
                <table class="table-base">
                    <caption class="sr-only">Your renewable services</caption>
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col">Next renewal</th>
                            <th scope="col">Renewal amount</th>
                            <th scope="col">Auto-renew</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($renewables as $r)
                            <tr>
                                <td>
                                    <span class="font-medium text-slate-900">{{ $r['label'] }}</span>
                                    <span class="block text-xs text-slate-500">{{ $r['sub_label'] }}</span>
                                </td>
                                <td>{{ $r['renewal_date']?->format('j M Y') ?? '—' }}</td>
                                <td>£{{ number_format($r['amount'], 2) }}<span class="text-slate-500">{{ $r['cycle'] }}</span></td>
                                <td>
                                    <form method="POST" action="{{ $r['toggle_url'] }}" class="flex items-center gap-2">
                                        @csrf
                                        <button type="submit" role="switch" aria-checked="{{ $r['auto_renew'] ? 'true' : 'false' }}"
                                                aria-label="Toggle auto-renew for {{ $r['label'] }}"
                                                class="inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition {{ $r['auto_renew'] ? 'bg-success' : 'bg-slate-300' }}">
                                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $r['auto_renew'] ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                                        </button>
                                        <span class="text-sm font-medium {{ $r['auto_renew'] ? 'text-success' : 'text-slate-500' }}">{{ $r['auto_renew'] ? 'On' : 'Off' }}</span>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Invoices --}}
    <section class="mt-8">
        <h2 class="text-lg font-bold">Invoices</h2>
        @if ($invoices->isEmpty())
            <div class="card mt-3 text-center text-slate-500">
                No invoices found. Your invoices will appear here after your first purchase.
            </div>
        @else
            <div class="table-wrap mt-3">
                <table class="table-base">
                    <caption class="sr-only">Your invoices</caption>
                    <thead>
                        <tr>
                            <th scope="col">Invoice</th>
                            <th scope="col">Date</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">View</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="font-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->created_at->format('j M Y') }}</td>
                                <td>£{{ number_format((float) $invoice->total, 2) }}</td>
                                <td><x-status-badge :status="$invoice->status" /></td>
                                <td class="text-right"><a href="{{ route('customer.invoices.show', $invoice) }}" class="btn-secondary btn-sm">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $invoices->links() }}</div>
        @endif
    </section>

    {{-- Subscriptions --}}
    @if ($subscriptions->isNotEmpty())
        <section class="mt-8">
            <h2 class="text-lg font-bold">Subscriptions &amp; renewals</h2>
            <div class="table-wrap mt-3">
                <table class="table-base">
                    <caption class="sr-only">Your subscriptions</caption>
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Next renewal</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td class="font-medium">{{ $subscription->product?->name ?? 'Service' }} <span class="text-slate-500">({{ $subscription->billing_cycle }})</span></td>
                                <td>£{{ number_format((float) $subscription->amount, 2) }}</td>
                                <td>{{ $subscription->next_renewal_date?->format('j M Y') ?? '—' }}</td>
                                <td><x-status-badge :status="$subscription->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
