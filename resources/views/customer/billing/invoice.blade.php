@extends('layouts.customer')

@section('title', $invoice->invoice_number)
@section('page-title', 'Invoice')

@section('content')
    <div class="flex items-center justify-between">
        <a href="{{ route('customer.billing.index') }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to billing</a>
        <a href="{{ route('customer.invoices.download', $invoice) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">Print / Save as PDF</a>
    </div>

    <div class="mt-4 card">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">{{ $invoice->invoice_number }}</h2>
                <p class="text-sm text-slate-500">Issued {{ $invoice->created_at->format('j M Y') }}</p>
            </div>
            <x-status-badge :status="$invoice->status" />
        </div>

        <div class="mt-6 table-wrap">
            <table class="table-base">
                <caption class="sr-only">Invoice items</caption>
                <thead>
                    <tr><th scope="col">Item</th><th scope="col" class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                    @forelse ($invoice->order?->items ?? [] as $item)
                        <tr>
                            <td>{{ $item->name }}@if ($item->domain_name)<span class="block text-sm text-slate-500">{{ $item->domain_name }}</span>@endif</td>
                            <td class="text-right">£{{ number_format((float) $item->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td>{{ $invoice->order?->order_number ?? 'Service' }}</td><td class="text-right">£{{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <dl class="mt-6 ml-auto max-w-xs space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-600">Subtotal</dt><dd>£{{ number_format((float) $invoice->subtotal, 2) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><dt>Total</dt><dd>£{{ number_format((float) $invoice->total, 2) }}</dd></div>
            <div class="flex justify-between text-success"><dt>Paid</dt><dd>£{{ number_format((float) $invoice->amount_paid, 2) }}</dd></div>
        </dl>
    </div>
@endsection
