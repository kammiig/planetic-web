@extends('layouts.public')

@section('title', 'Secure checkout')
@section('meta_description', 'Complete your Planetic Web order securely. Pay on-site with card — your domain, hosting and website are set up automatically after payment.')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    {{-- Stripe.js must be served from Stripe's domain (PCI requirement). --}}
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
    <section class="bg-slate-50 py-10 sm:py-14">
        <div class="container-px">
            <nav aria-label="Breadcrumb" class="text-sm">
                <ol class="flex items-center gap-2 text-slate-500">
                    <li><a href="{{ route('cart.index') }}" class="hover:text-primary-600 hover:underline">Cart</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-medium text-slate-700" aria-current="page">Checkout</li>
                </ol>
            </nav>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold sm:text-3xl">Secure checkout</h1>
                    <p class="mt-1 text-sm text-slate-600">Complete your order safely — payment happens right here on the page.</p>
                </div>
                <p class="inline-flex items-center gap-2 text-sm font-medium text-slate-500">
                    <svg class="h-4 w-4 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    256-bit SSL encrypted · Powered by Stripe
                </p>
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-3">
                {{-- ===================== LEFT: steps ===================== --}}
                <div class="order-2 lg:order-1 lg:col-span-2">
                    @guest
                        {{-- Step 1: sign up / sign in --}}
                        <div class="card">
                            <span class="badge badge-primary">Step 1 of 3</span>
                            <h2 class="mt-3 text-xl font-bold">Sign in or create your account</h2>
                            <p class="mt-1 text-sm text-slate-600">You'll need an account to complete your order and manage your domain, hosting and website afterwards.</p>
                            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                                <a href="{{ route('register') }}?intended={{ urlencode(route('checkout.index')) }}" class="btn-primary sm:flex-1">Create an account</a>
                                <a href="{{ route('login') }}?intended={{ urlencode(route('checkout.index')) }}" class="btn-secondary sm:flex-1">I already have an account</a>
                            </div>
                            <p class="mt-4 text-xs text-slate-500">Your cart is saved and will be waiting for you after you sign in.</p>
                        </div>
                    @else
                        @if (! auth()->user()->hasVerifiedEmail())
                            <div class="card">
                                <div class="alert alert-warning" role="alert">
                                    <strong class="font-semibold">Please verify your email address before paying.</strong>
                                    <p class="mt-1">We sent a verification link to <span class="font-medium">{{ auth()->user()->email }}</span>.</p>
                                </div>
                                <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
                                    @csrf
                                    <button type="submit" class="btn-primary">Resend verification email</button>
                                </form>
                            </div>
                        @else
                            <div
                                x-data="checkout(@js([
                                    'intentUrl' => route('checkout.payment-intent'),
                                    'successUrl' => route('checkout.success'),
                                    'publishableKey' => $publishableKey,
                                    'steps' => ['review', 'billing', 'payment'],
                                ]))"
                                x-cloak
                            >
                                {{-- Step indicator --}}
                                <ol class="mb-6 grid grid-cols-3 gap-2" aria-label="Checkout progress">
                                    @foreach (['review' => 'Review order', 'billing' => 'Billing details', 'payment' => 'Payment'] as $key => $title)
                                        <li class="flex items-center gap-2 rounded-[10px] border px-3 py-2"
                                            x-bind:class="isActive('{{ $key }}') ? 'border-primary-500 bg-primary-50' : (isDone('{{ $key }}') ? 'border-success/40 bg-success/5' : 'border-slate-200 bg-white')"
                                            x-bind:aria-current="isActive('{{ $key }}') ? 'step' : null">
                                            <span class="grid h-6 w-6 flex-shrink-0 place-items-center rounded-full text-xs font-bold"
                                                  x-bind:class="isActive('{{ $key }}') ? 'bg-primary-500 text-white' : (isDone('{{ $key }}') ? 'bg-success text-white' : 'bg-slate-200 text-slate-600')">
                                                <span x-show="!isDone('{{ $key }}')">{{ $loop->iteration }}</span>
                                                <svg x-show="isDone('{{ $key }}')" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                            </span>
                                            <span class="truncate text-sm font-semibold text-slate-700">{{ $title }}</span>
                                        </li>
                                    @endforeach
                                </ol>

                                {{-- Step: Review --}}
                                <div data-step="review" x-show="isActive('review')" class="card">
                                    <h2 data-step-heading class="text-xl font-bold focus:outline-none">Review your order</h2>
                                    <p class="mt-1 text-sm text-slate-600">Check the services below, then continue to billing.</p>

                                    <ul class="mt-5 divide-y divide-slate-200">
                                        @foreach ($lineItems as $item)
                                            <li class="flex items-start justify-between gap-4 py-4">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                                    @if ($item->domain_name)
                                                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $item->domain_name }}</p>
                                                    @endif
                                                    @if ($item->item_type === \App\Enums\ItemType::WebsitePackage)
                                                        <p class="mt-1 text-sm font-medium text-success">Includes a free domain &amp; hosting for the first year.</p>
                                                    @endif
                                                </div>
                                                <p class="whitespace-nowrap font-semibold text-slate-900">£{{ number_format((float) $item->total, 2) }}</p>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <a href="{{ route('cart.index') }}" class="text-sm font-medium text-primary-600 hover:underline">Edit cart</a>
                                        <button type="button" @click="next()" class="btn-primary sm:w-auto">Continue to billing</button>
                                    </div>
                                </div>

                                {{-- Step: Billing --}}
                                <div data-step="billing" x-show="isActive('billing')" x-cloak class="card">
                                    <h2 data-step-heading class="text-xl font-bold focus:outline-none">Billing details</h2>
                                    <p class="mt-1 text-sm text-slate-600">We use these details for your invoice and order confirmation.</p>

                                    <div class="mt-4 alert alert-danger" role="alert" x-show="formError" x-text="formError" x-cloak></div>

                                    <form x-ref="billingForm" @submit.prevent="continueToPayment()" class="mt-5 space-y-4" novalidate>
                                        <x-checkout-field name="name" label="Full name" autocomplete="name" :required="true" :value="auth()->user()->name" />
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <x-checkout-field name="phone" label="Phone" type="tel" autocomplete="tel" :required="true" :value="auth()->user()->phone" />
                                            <x-checkout-field name="company_name" label="Company (optional)" autocomplete="organization" :value="auth()->user()->company_name" />
                                        </div>
                                        <x-checkout-field name="billing_address_line_1" label="Address line 1" autocomplete="address-line1" :required="true" :value="auth()->user()->billing_address_line_1" />
                                        <x-checkout-field name="billing_address_line_2" label="Address line 2 (optional)" autocomplete="address-line2" :value="auth()->user()->billing_address_line_2" />
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <x-checkout-field name="billing_city" label="City" autocomplete="address-level2" :required="true" :value="auth()->user()->billing_city" />
                                            <x-checkout-field name="billing_state" label="County / State (optional)" autocomplete="address-level1" :value="auth()->user()->billing_state" />
                                        </div>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <x-checkout-field name="billing_postcode" label="Postcode" autocomplete="postal-code" :required="true" :value="auth()->user()->billing_postcode" />
                                            <x-checkout-field name="billing_country" label="Country code" autocomplete="country" :required="true" :value="auth()->user()->billing_country ?? 'GB'" help="2-letter code, e.g. GB" />
                                        </div>

                                        <div class="flex items-start gap-3 border-t border-slate-200 pt-4">
                                            <input type="checkbox" id="co_terms" name="terms" value="1" class="mt-1 h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500"
                                                   x-bind:aria-invalid="fieldError('terms') ? 'true' : null" aria-describedby="co_terms_err">
                                            <label for="co_terms" class="text-sm text-slate-600">
                                                I agree to the <a href="{{ route('legal.terms') }}" class="font-medium text-primary-600 hover:underline">Terms of Use</a>,
                                                <a href="{{ route('legal.refund') }}" class="font-medium text-primary-600 hover:underline">Refund Policy</a>, and understand that
                                                domain &amp; hosting renew after the first year (<a href="{{ route('legal.renewal') }}" class="font-medium text-primary-600 hover:underline">Renewal Policy</a>).
                                            </label>
                                        </div>
                                        <p id="co_terms_err" class="field-error" role="alert" x-show="fieldError('terms')" x-text="fieldError('terms')" x-cloak></p>

                                        <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                                            <button type="button" @click="back()" class="btn-secondary sm:w-auto">Back</button>
                                            <button type="submit" class="btn-primary sm:w-auto" x-bind:disabled="initialising">
                                                <span x-show="!initialising">Continue to payment</span>
                                                <span x-show="initialising" x-cloak class="inline-flex items-center gap-2">
                                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/></svg>
                                                    Starting secure payment…
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                {{-- Step: Payment --}}
                                <div data-step="payment" x-show="isActive('payment')" x-cloak class="card">
                                    <h2 data-step-heading class="text-xl font-bold focus:outline-none">Payment</h2>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Paying for order <span class="font-semibold text-slate-700" x-text="orderNumber"></span>. Enter your card details below.
                                    </p>

                                    <div class="mt-4 alert alert-danger" role="alert" x-show="formError" x-text="formError" x-cloak></div>

                                    {{-- Stripe Payment Element mounts here --}}
                                    <div x-ref="paymentElement" class="mt-5 min-h-[44px]" aria-label="Card details"></div>

                                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <button type="button" @click="back()" class="btn-secondary sm:w-auto" x-bind:disabled="paying">Back</button>
                                        <button type="button" @click="pay()" class="btn-primary sm:w-auto" x-bind:disabled="paying || !paymentReady">
                                            <span x-show="!paying" class="inline-flex items-center gap-2">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                Pay £{{ number_format((float) $total, 2) }} now
                                            </span>
                                            <span x-show="paying" x-cloak class="inline-flex items-center gap-2">
                                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/></svg>
                                                Processing payment…
                                            </span>
                                        </button>
                                    </div>

                                    <p class="mt-4 inline-flex items-center gap-2 text-xs text-slate-500">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        Payments are processed securely by Stripe. We never see or store your full card details.
                                    </p>
                                </div>
                            </div>

                            <noscript>
                                <div class="alert alert-warning mt-4" role="alert">JavaScript is required to complete payment securely. Please enable JavaScript and reload this page.</div>
                            </noscript>
                        @endif
                    @endguest
                </div>

                {{-- ===================== RIGHT: live order summary ===================== --}}
                <aside class="order-1 lg:order-2 lg:col-span-1" aria-label="Order summary">
                    <div class="card lg:sticky lg:top-24">
                        <h2 class="text-lg font-bold">Order summary</h2>

                        <ul class="mt-4 divide-y divide-slate-200">
                            @foreach ($lineItems as $item)
                                <li class="flex items-start justify-between gap-3 py-3 text-sm">
                                    <span class="min-w-0">
                                        <span class="font-medium text-slate-900">{{ $item->name }}</span>
                                        @if ($item->domain_name)
                                            <span class="mt-0.5 block truncate text-slate-500">{{ $item->domain_name }}</span>
                                        @endif
                                        @if ($item->item_type === \App\Enums\ItemType::WebsitePackage)
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-semibold text-success">Free domain &amp; hosting · year 1</span>
                                        @endif
                                    </span>
                                    <span class="whitespace-nowrap font-semibold text-slate-900">£{{ number_format((float) $item->total, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 text-base font-bold text-slate-900">
                            <span>Total due today</span>
                            <span>£{{ number_format((float) $total, 2) }}</span>
                        </div>

                        @if ($freeYearNotice)
                            <p class="mt-3 rounded-[10px] bg-slate-50 p-3 text-xs text-slate-500">{{ $freeYearNotice }}</p>
                        @endif

                        <ul class="mt-4 space-y-2 text-xs text-slate-500">
                            <li class="flex items-center gap-2"><svg class="h-4 w-4 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Secure on-site card payment</li>
                            <li class="flex items-center gap-2"><svg class="h-4 w-4 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Automatic setup after payment</li>
                            <li class="flex items-center gap-2"><svg class="h-4 w-4 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> UK support &amp; managed renewals</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
