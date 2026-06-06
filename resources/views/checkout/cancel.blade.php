@extends('layouts.public')

@section('title', 'Checkout cancelled')

@section('content')
    <section class="container-px py-16">
        <div class="mx-auto max-w-xl text-center">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-slate-100 text-slate-500" aria-hidden="true">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
            </span>
            <h1 class="mt-6 text-3xl font-bold">Checkout cancelled</h1>
            <p class="mt-3 text-slate-600">No payment was taken. Your cart is still saved — you can pick up where you left off.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('cart.index') }}" class="btn-primary">Return to cart</a>
                <a href="{{ route('home') }}" class="btn-secondary">Back to home</a>
            </div>
        </div>
    </section>
@endsection
