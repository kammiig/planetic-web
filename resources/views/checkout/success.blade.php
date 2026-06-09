@extends('layouts.public')

@php
    use App\Enums\OrderStatus;
    $redirectStatus = request()->query('redirect_status');
    $failed = $redirectStatus === 'failed' || ($order && $order->status === OrderStatus::Failed);
    $settling = ! $failed && $order && in_array($order->status, [OrderStatus::Pending, OrderStatus::Provisioning], true);
@endphp

@section('title', $failed ? 'Payment not completed' : 'Payment received')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    @if ($settling)
        {{-- Reflect provisioning progress without the customer having to refresh. --}}
        <meta http-equiv="refresh" content="15">
    @endif
@endpush

@section('content')
    <section class="container-px py-16">
        @if ($failed)
            <div class="mx-auto max-w-xl text-center" aria-live="polite">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-danger/10 text-danger" aria-hidden="true">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                </span>
                <h1 class="mt-6 text-3xl font-bold">Your payment was not completed</h1>
                <p class="mt-3 text-slate-600">No money has been taken. This can happen if a card is declined or a verification step is cancelled. You can try again — your cart and details are saved.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('checkout.index') }}" class="btn-primary">Try payment again</a>
                    <a href="{{ route('contact') }}" class="btn-secondary">Contact support</a>
                </div>
            </div>
        @else
            <div class="mx-auto max-w-xl text-center" aria-live="polite">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-success/10 text-success" aria-hidden="true">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <h1 class="mt-6 text-3xl font-bold">Thank you — your payment is confirmed</h1>

                @if ($order)
                    <p class="mt-3 text-slate-600">
                        Order <span class="font-semibold">{{ $order->order_number }}</span>. We're setting up your services now.
                        You'll receive an email shortly and can track progress in your dashboard.
                    </p>
                    <div class="mt-4 flex justify-center">
                        <x-status-badge :status="$order->status" />
                    </div>
                    @if ($settling)
                        <p class="mt-3 text-sm text-slate-500">This page updates automatically while your services are provisioned.</p>
                    @endif
                @else
                    <p class="mt-3 text-slate-600">
                        We're confirming your payment securely. Your services will be set up automatically once payment is verified,
                        and you'll receive a confirmation email shortly.
                    </p>
                @endif

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ url('/dashboard') }}" class="btn-primary">Go to your dashboard</a>
                    <a href="{{ route('home') }}" class="btn-secondary">Back to home</a>
                </div>

                <p class="mt-6 text-sm text-slate-500">
                    Setup usually completes within a few minutes. If anything needs attention, our team is notified automatically.
                </p>
            </div>
        @endif
    </section>
@endsection
