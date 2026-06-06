@extends('layouts.public')

@section('title', 'Checkout')

@section('content')
    <section class="container-px py-12">
        <h1 class="text-3xl font-bold">Checkout</h1>

        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            {{-- Left: details / auth --}}
            <div class="lg:col-span-2 space-y-6">
                @guest
                    <div class="card">
                        <h2 class="text-lg font-bold">Sign in or create an account</h2>
                        <p class="mt-1 text-sm text-slate-600">You'll need an account to complete your order and manage your services.</p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
                            <a href="{{ route('register') }}" class="btn-primary">Create account</a>
                        </div>
                    </div>
                @else
                    @if (! auth()->user()->hasVerifiedEmail())
                        <div class="alert alert-warning">
                            Please verify your email address before paying.
                            <a href="{{ route('verification.notice') }}" class="font-semibold underline">Resend verification email</a>.
                        </div>
                    @else
                        <form method="POST" action="{{ route('checkout.start') }}" class="card space-y-4" novalidate>
                            @csrf
                            <h2 class="text-lg font-bold">Billing details</h2>

                            <x-field name="name" label="Full name" autocomplete="name" :value="auth()->user()->name" :required="true" />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-field name="phone" label="Phone" type="tel" autocomplete="tel" :value="auth()->user()->phone" :required="true" />
                                <x-field name="company_name" label="Company (optional)" autocomplete="organization" :value="auth()->user()->company_name" />
                            </div>
                            <x-field name="billing_address_line_1" label="Address line 1" autocomplete="address-line1" :value="auth()->user()->billing_address_line_1" :required="true" />
                            <x-field name="billing_address_line_2" label="Address line 2 (optional)" autocomplete="address-line2" :value="auth()->user()->billing_address_line_2" />
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-field name="billing_city" label="City" autocomplete="address-level2" :value="auth()->user()->billing_city" :required="true" />
                                <x-field name="billing_state" label="County / State (optional)" autocomplete="address-level1" :value="auth()->user()->billing_state" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-field name="billing_postcode" label="Postcode" autocomplete="postal-code" :value="auth()->user()->billing_postcode" :required="true" />
                                <x-field name="billing_country" label="Country code" autocomplete="country" :value="auth()->user()->billing_country ?? 'GB'" :required="true" help="2-letter code, e.g. GB" />
                            </div>

                            <div class="flex items-start gap-3 border-t border-slate-200 pt-4">
                                <input type="checkbox" id="terms" name="terms" value="1" class="mt-1 h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500" @checked(old('terms'))>
                                <label for="terms" class="text-sm text-slate-600">
                                    I agree to the <a href="{{ route('legal.terms') }}" class="font-medium text-primary-600 hover:underline">Terms of Use</a>,
                                    <a href="{{ route('legal.refund') }}" class="font-medium text-primary-600 hover:underline">Refund Policy</a>, and understand that
                                    domain and hosting renew after the first year (<a href="{{ route('legal.renewal') }}" class="font-medium text-primary-600 hover:underline">Renewal Policy</a>).
                                </label>
                            </div>
                            @error('terms')<p class="field-error" role="alert">{{ $message }}</p>@enderror

                            <button type="submit" class="btn-primary w-full">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Pay securely with Stripe
                            </button>
                            <p class="text-center text-xs text-slate-500">You'll be redirected to Stripe's secure checkout. We never see or store your card details.</p>
                        </form>
                    @endif
                @endguest
            </div>

            {{-- Right: order summary --}}
            <aside class="lg:col-span-1">
                <div class="card sticky top-24">
                    <h2 class="text-lg font-bold">Order summary</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($cart->items as $item)
                            <li class="flex justify-between gap-3 text-sm">
                                <span>
                                    <span class="font-medium text-slate-900">{{ $item->name }}</span>
                                    @if ($item->domain_name)<span class="block text-slate-500">{{ $item->domain_name }}</span>@endif
                                    @if ($item->item_type === \App\Enums\ItemType::WebsitePackage)
                                        <span class="block text-success">Free domain &amp; hosting (year 1)</span>
                                    @endif
                                </span>
                                <span class="font-semibold">£{{ number_format((float) $item->total, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex justify-between border-t border-slate-200 pt-3 text-base font-bold">
                        <span>Total due today</span>
                        <span>£{{ number_format((float) $cart->total, 2) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ $freeYearNotice }}</p>
                </div>
            </aside>
        </div>
    </section>
@endsection
