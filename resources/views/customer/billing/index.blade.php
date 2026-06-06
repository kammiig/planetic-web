@extends('layouts.customer')

@section('title', 'Billing')
@section('page-title', 'Billing')

@section('content')
    {{-- Invoices --}}
    <section>
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
