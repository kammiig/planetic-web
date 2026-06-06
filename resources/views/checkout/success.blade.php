@extends('layouts.public')

@section('title', 'Payment received')

@section('content')
    <section class="container-px py-16">
        <div class="mx-auto max-w-xl text-center" aria-live="polite">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-success/10 text-success" aria-hidden="true">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <h1 class="mt-6 text-3xl font-bold">Thank you — your payment is being confirmed</h1>

            @if ($order)
                <p class="mt-3 text-slate-600">
                    Order <span class="font-semibold">{{ $order->order_number }}</span>. We're confirming your payment and
                    setting up your services. You'll receive an email shortly and can track progress in your dashboard.
                </p>
                <div class="mt-4 flex justify-center">
                    <x-status-badge :status="$order->status" />
                </div>
            @else
                <p class="mt-3 text-slate-600">
                    We're confirming your payment securely. Your services will be set up automatically once payment is verified.
                    You'll receive a confirmation email shortly.
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
    </section>
@endsection
