@extends('layouts.public')

@section('title', "Let's complete your purchase")
@section('meta_description', 'Complete your Planetic Web order securely. Pay on-site with card — your domain, hosting and website are set up automatically after payment.')

@push('head')
    <meta name="robots" content="noindex,nofollow">
    {{-- Stripe.js must be served from Stripe's domain (PCI requirement). --}}
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')
    <section class="bg-slate-50 py-10 sm:py-14">
        <div class="container-px">
            {{-- Centered header --}}
            <div class="mx-auto max-w-2xl text-center">
                <nav aria-label="Breadcrumb" class="text-sm">
                    <ol class="flex items-center justify-center gap-2 text-slate-500">
                        <li><a href="{{ route('cart.index') }}" class="hover:text-primary-600 hover:underline">Cart</a></li>
                        <li aria-hidden="true">/</li>
                        <li class="font-medium text-slate-700" aria-current="page">Checkout</li>
                    </ol>
                </nav>
                <h1 class="mt-4 text-3xl font-extrabold sm:text-4xl">Let's complete your purchase</h1>
                <p class="mt-2 text-slate-600">Almost there! Create or sign into your account and enter your billing details.</p>
            </div>

            @guest
                {{-- ===================== GUEST: INLINE SIGN UP / SIGN IN ===================== --}}
                {{-- The customer authenticates right here — no redirect, the cart is kept. --}}
                <div
                    x-data="checkoutAuth(@js([
                        'mode' => 'register',
                        'registerUrl' => route('checkout.register'),
                        'loginUrl' => route('checkout.login'),
                    ]))"
                    class="mx-auto mt-10 grid max-w-5xl gap-6 lg:grid-cols-3"
                >
                    <div class="space-y-3 lg:col-span-2">
                        {{-- Step 1: Account (active) --}}
                        <div class="rounded-[14px] border border-primary-500 bg-white shadow-soft">
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-primary-500 text-sm font-bold text-white">1</span>
                                    <h2 class="text-base font-bold">Your account</h2>
                                </div>
                                <div class="inline-flex rounded-[10px] border border-slate-200 p-0.5 text-sm font-semibold" role="tablist" aria-label="Create an account or sign in">
                                    <button type="button" @click="switchTo('register')" role="tab" x-bind:aria-selected="mode === 'register'"
                                            class="rounded-[8px] px-3 py-1.5 transition"
                                            x-bind:class="mode === 'register' ? 'bg-primary-500 text-white' : 'text-slate-600 hover:text-primary-600'">
                                        Create account
                                    </button>
                                    <button type="button" @click="switchTo('login')" role="tab" x-bind:aria-selected="mode === 'login'"
                                            class="rounded-[8px] px-3 py-1.5 transition"
                                            x-bind:class="mode === 'login' ? 'bg-primary-500 text-white' : 'text-slate-600 hover:text-primary-600'">
                                        Sign in
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 px-5 py-5">
                                <div class="alert alert-danger mb-4" role="alert" x-show="formError" x-text="formError" x-cloak></div>

                                {{-- Create account (inline) --}}
                                <form x-show="mode === 'register'" x-ref="registerForm" @submit.prevent="submitRegister()" class="space-y-4" novalidate>
                                    <p class="text-sm text-slate-600">Create your account to manage your domain, hosting and website — you'll stay right here and continue to payment.</p>
                                    <x-checkout-field name="name" label="Full name" autocomplete="name" :required="true" />
                                    <x-checkout-field name="email" label="Email address" type="email" autocomplete="email" :required="true" />
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <x-checkout-field name="password" label="Password" type="password" autocomplete="new-password" :required="true" help="At least 10 characters with upper & lower case, a number and a symbol." />
                                        <x-checkout-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" :required="true" />
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" id="ca_terms" name="terms" value="1" class="mt-1 h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500" aria-describedby="ca_terms_err">
                                        <label for="ca_terms" class="text-sm text-slate-600">
                                            I agree to the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener" class="font-medium text-primary-600 hover:underline">Terms of Use</a>
                                            and <a href="{{ route('legal.renewal') }}" target="_blank" rel="noopener" class="font-medium text-primary-600 hover:underline">Renewal Policy</a>.
                                        </label>
                                    </div>
                                    <p id="ca_terms_err" class="field-error" role="alert" x-show="fieldError('terms')" x-text="fieldError('terms')" x-cloak></p>

                                    <button type="submit" class="btn-primary w-full sm:w-auto" x-bind:disabled="submitting">
                                        <span x-show="!submitting">Create account &amp; continue</span>
                                        <span x-show="submitting" x-cloak>Creating your account…</span>
                                    </button>
                                    <p class="text-xs text-slate-500">We'll email you a verification link in the background — it never holds up your order.</p>
                                </form>

                                {{-- Sign in (inline) --}}
                                <form x-show="mode === 'login'" x-cloak x-ref="loginForm" @submit.prevent="submitLogin()" class="space-y-4" novalidate>
                                    <p class="text-sm text-slate-600">Welcome back — sign in to continue with your order. Your cart is safe.</p>
                                    <x-checkout-field name="email" label="Email address" type="email" autocomplete="email" :required="true" />
                                    <x-checkout-field name="password" label="Password" type="password" autocomplete="current-password" :required="true" />
                                    <div class="flex items-center justify-between gap-4">
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="remember" value="1" class="h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500">
                                            Remember me
                                        </label>
                                        <a href="{{ route('password.request') }}" target="_blank" rel="noopener" class="text-sm font-medium text-primary-600 hover:underline">Forgot password?</a>
                                    </div>
                                    <button type="submit" class="btn-primary w-full sm:w-auto" x-bind:disabled="submitting">
                                        <span x-show="!submitting">Sign in &amp; continue</span>
                                        <span x-show="submitting" x-cloak>Signing you in…</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Collapsed future steps --}}
                        @foreach (['Confirm your plan', 'Enter your billing address', 'Choose a payment method'] as $i => $label)
                            <div class="rounded-[14px] border border-slate-200 bg-white px-5 py-4">
                                <div class="flex items-center gap-3 text-slate-400">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-sm font-bold">{{ $i + 2 }}</span>
                                    <h2 class="text-base font-semibold">{{ $label }}</h2>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <aside class="lg:col-span-1" aria-label="Order summary">
                        @include('checkout.partials.summary', ['showPay' => false, 'lineItems' => $lineItems, 'total' => $total, 'freeYearNotice' => $freeYearNotice])
                    </aside>
                </div>
            @else
                {{-- ===================== AUTHENTICATED ===================== --}}
                    @php
                        // Step numbering shown to the customer (Account is always 1).
                        $stepNo = [
                            'domain' => 2,
                            'review' => $needsDomain ? 3 : 2,
                            'billing' => $needsDomain ? 4 : 3,
                            'payment' => $needsDomain ? 5 : 4,
                        ];
                    @endphp
                    <div
                        x-data="checkout(@js([
                            'intentUrl' => route('checkout.payment-intent'),
                            'freeUrl' => route('checkout.complete-free'),
                            'successUrl' => route('checkout.success'),
                            'domainUrl' => route('checkout.domain'),
                            'searchUrl' => route('domains.search'),
                            'total' => (float) $total,
                            'publishableKey' => $publishableKey,
                            'steps' => $needsDomain ? ['domain', 'review', 'billing', 'payment'] : ['review', 'billing', 'payment'],
                            'domainChoice' => $domainChoice,
                            'domainIsFree' => $domainIsFree,
                            'initialStep' => $initialStep,
                        ]))"
                        x-cloak
                        class="mx-auto mt-10 grid max-w-5xl gap-6 lg:grid-cols-3"
                    >
                        <div class="space-y-3 lg:col-span-2">
                            {{-- Step 1: Account (complete) --}}
                            <div class="rounded-[14px] border border-success/40 bg-white px-5 py-4 shadow-soft">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-7 w-7 place-items-center rounded-full bg-success text-white" aria-hidden="true">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                        <div>
                                            <h2 class="text-base font-bold">Account</h2>
                                            <p class="text-sm text-slate-500">Signed in as {{ auth()->user()->email }}</p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-slate-500 hover:text-primary-600 hover:underline">Sign out</button>
                                    </form>
                                </div>
                            </div>

                            @if ($needsDomain)
                                {{-- Step 2: Choose your domain (hosting / website package) --}}
                                <div class="rounded-[14px] border bg-white shadow-soft transition"
                                     x-bind:class="isActive('domain') ? 'border-primary-500' : 'border-slate-200'">
                                    <button type="button" @click="goTo(steps.indexOf('domain'))" class="flex w-full items-center gap-3 px-5 py-4 text-left"
                                            x-bind:class="isDone('domain') ? 'cursor-pointer' : 'cursor-default'">
                                        <span class="grid h-7 w-7 flex-shrink-0 place-items-center rounded-full text-sm font-bold"
                                              x-bind:class="isActive('domain') ? 'bg-primary-500 text-white' : (isDone('domain') ? 'bg-success text-white' : 'bg-slate-100 text-slate-500')">
                                            <span x-show="!isDone('domain')">{{ $stepNo['domain'] }}</span>
                                            <svg x-show="isDone('domain')" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                        <div class="flex-1">
                                            <h2 data-step-heading class="text-base font-bold focus:outline-none">Choose your domain</h2>
                                            <p class="text-sm text-slate-500" x-show="isDone('domain')" x-cloak>
                                                <span x-show="domainMode === 'later'">You'll provide your domain later</span>
                                                <span x-show="domainMode !== 'later'" x-text="domainQuery"></span>
                                            </p>
                                        </div>
                                        <span x-show="isDone('domain')" x-cloak class="text-sm font-medium text-primary-600">Edit</span>
                                    </button>

                                    <div data-step="domain" x-show="isActive('domain')" x-cloak tabindex="-1" aria-label="Choose your domain" class="border-t border-slate-100 px-5 py-5 focus:outline-none">
                                        <div class="alert alert-danger mb-4" role="alert" x-show="formError" x-text="formError" x-cloak></div>

                                        @if ($domainIsFree)
                                            <p class="mb-4 inline-flex items-center gap-1.5 rounded-full bg-success/10 px-3 py-1 text-sm font-semibold text-success">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                                A new domain is FREE for the first year with your order
                                            </p>
                                        @endif

                                        <div class="space-y-3" role="radiogroup" aria-label="Domain options">
                                            {{-- Option 1: register a new domain --}}
                                            <label class="flex cursor-pointer items-start gap-3 rounded-[12px] border p-4 transition"
                                                   x-bind:class="domainMode === 'new' ? 'border-primary-500 bg-primary-50/40' : 'border-slate-200 hover:border-slate-300'">
                                                <input type="radio" name="domain_mode" value="new" class="mt-1 h-5 w-5 border-slate-300 text-primary-500 focus:ring-primary-500"
                                                       x-model="domainMode" @change="switchDomainMode('new')">
                                                <span class="flex-1">
                                                    <span class="block font-semibold text-slate-900">Register a new domain{{ $domainIsFree ? ' — free first year' : '' }}</span>
                                                    <span class="mt-0.5 block text-sm text-slate-500">We'll register it for you and set everything up automatically.</span>

                                                    <span class="mt-3 flex flex-col gap-2 sm:flex-row" x-show="domainMode === 'new'" x-cloak>
                                                        <input type="text" x-model="domainQuery" @keydown.enter.prevent="checkDomain()"
                                                               placeholder="yourbusiness.com" inputmode="url" autocomplete="off" spellcheck="false"
                                                               class="input flex-1" aria-label="Domain to register"
                                                               x-bind:class="fieldError('domain_name') && 'input-error'">
                                                        <button type="button" @click="checkDomain()" class="btn-secondary whitespace-nowrap" x-bind:disabled="domainChecking">
                                                            <span x-show="!domainChecking">Check availability</span>
                                                            <span x-show="domainChecking" x-cloak>Checking…</span>
                                                        </button>
                                                    </span>
                                                    <span class="mt-2 block text-sm font-medium" x-show="domainMode === 'new' && domainCheck" x-cloak
                                                          x-bind:class="domainCheck && domainCheck.available ? 'text-success' : 'text-danger'"
                                                          x-text="domainCheck && domainCheck.label" role="status"></span>
                                                </span>
                                            </label>

                                            {{-- Option 2: bring an existing domain --}}
                                            <label class="flex cursor-pointer items-start gap-3 rounded-[12px] border p-4 transition"
                                                   x-bind:class="domainMode === 'existing' ? 'border-primary-500 bg-primary-50/40' : 'border-slate-200 hover:border-slate-300'">
                                                <input type="radio" name="domain_mode" value="existing" class="mt-1 h-5 w-5 border-slate-300 text-primary-500 focus:ring-primary-500"
                                                       x-model="domainMode" @change="switchDomainMode('existing')">
                                                <span class="flex-1">
                                                    <span class="block font-semibold text-slate-900">Use a domain I already own</span>
                                                    <span class="mt-0.5 block text-sm text-slate-500">Keep it registered where it is — we'll connect your services to it and show you the DNS settings.</span>
                                                    <span class="mt-3 block" x-show="domainMode === 'existing'" x-cloak>
                                                        <input type="text" x-model="domainQuery"
                                                               placeholder="yourdomain.co.uk" inputmode="url" autocomplete="off" spellcheck="false"
                                                               class="input w-full" aria-label="Your existing domain"
                                                               x-bind:class="fieldError('domain_name') && 'input-error'">
                                                    </span>
                                                </span>
                                            </label>

                                            @if ($canDeferDomain)
                                                {{-- Option 3: decide later (website package only) --}}
                                                <label class="flex cursor-pointer items-start gap-3 rounded-[12px] border p-4 transition"
                                                       x-bind:class="domainMode === 'later' ? 'border-primary-500 bg-primary-50/40' : 'border-slate-200 hover:border-slate-300'">
                                                    <input type="radio" name="domain_mode" value="later" class="mt-1 h-5 w-5 border-slate-300 text-primary-500 focus:ring-primary-500"
                                                           x-model="domainMode" @change="switchDomainMode('later')">
                                                    <span class="flex-1">
                                                        <span class="block font-semibold text-slate-900">I'll decide my domain later</span>
                                                        <span class="mt-0.5 block text-sm text-slate-500">Your website project starts now; we'll ask for your domain from your dashboard before anything goes live.</span>
                                                    </span>
                                                </label>
                                            @endif
                                        </div>

                                        <p class="field-error mt-3" role="alert" x-show="fieldError('domain_name')" x-text="fieldError('domain_name')" x-cloak></p>
                                        <p class="field-error mt-1" role="alert" x-show="fieldError('domain_source')" x-text="fieldError('domain_source')" x-cloak></p>

                                        <div class="mt-5 flex items-center justify-end">
                                            <button type="button" @click="continueDomain()" class="btn-primary" x-bind:disabled="domainSaving">
                                                <span x-show="!domainSaving">Continue</span>
                                                <span x-show="domainSaving" x-cloak>Saving…</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Confirm your plan --}}
                            <div class="rounded-[14px] border bg-white shadow-soft transition"
                                 x-bind:class="isActive('review') ? 'border-primary-500' : 'border-slate-200'">
                                <button type="button" @click="goTo(steps.indexOf('review'))" class="flex w-full items-center gap-3 px-5 py-4 text-left"
                                        x-bind:class="isDone('review') ? 'cursor-pointer' : 'cursor-default'">
                                    <span class="grid h-7 w-7 flex-shrink-0 place-items-center rounded-full text-sm font-bold"
                                          x-bind:class="isActive('review') ? 'bg-primary-500 text-white' : (isDone('review') ? 'bg-success text-white' : 'bg-slate-100 text-slate-500')">
                                        <span x-show="!isDone('review')">{{ $stepNo['review'] }}</span>
                                        <svg x-show="isDone('review')" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>
                                    <h2 data-step-heading class="flex-1 text-base font-bold focus:outline-none">Confirm your plan</h2>
                                    <span x-show="isDone('review')" x-cloak class="text-sm font-medium text-primary-600">Edit</span>
                                </button>

                                <div data-step="review" x-show="isActive('review')" x-cloak tabindex="-1" aria-label="Confirm your plan" class="border-t border-slate-100 px-5 py-5 focus:outline-none">
                                    <ul class="divide-y divide-slate-100">
                                        @foreach ($lineItems as $item)
                                            <li class="flex items-start justify-between gap-4 py-3 first:pt-0">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                                    @if ($item->domain_name)<p class="mt-0.5 truncate text-sm text-slate-500">{{ $item->domain_name }}</p>@endif
                                                    @if ($item->item_type === \App\Enums\ItemType::WebsitePackage)
                                                        <p class="mt-1 text-sm font-medium text-success">Includes a free domain &amp; hosting for the first year.</p>
                                                    @endif
                                                </div>
                                                <p class="whitespace-nowrap font-semibold text-slate-900">£{{ number_format((float) $item->total, 2) }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="mt-4 flex items-center justify-between">
                                        <a href="{{ route('cart.index') }}" class="text-sm font-medium text-primary-600 hover:underline">Edit cart</a>
                                        <button type="button" @click="next()" class="btn-primary">Continue to billing</button>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 3: Billing address --}}
                            <div class="rounded-[14px] border bg-white shadow-soft transition"
                                 x-bind:class="isActive('billing') ? 'border-primary-500' : 'border-slate-200'">
                                <button type="button" @click="goTo(steps.indexOf('billing'))" class="flex w-full items-center gap-3 px-5 py-4 text-left"
                                        x-bind:class="isDone('billing') ? 'cursor-pointer' : 'cursor-default'">
                                    <span class="grid h-7 w-7 flex-shrink-0 place-items-center rounded-full text-sm font-bold"
                                          x-bind:class="isActive('billing') ? 'bg-primary-500 text-white' : (isDone('billing') ? 'bg-success text-white' : 'bg-slate-100 text-slate-500')">
                                        <span x-show="!isDone('billing')">{{ $stepNo['billing'] }}</span>
                                        <svg x-show="isDone('billing')" x-cloak class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>
                                    <h2 data-step-heading class="flex-1 text-base font-bold focus:outline-none">Enter your billing address</h2>
                                </button>

                                <div data-step="billing" x-show="isActive('billing')" x-cloak tabindex="-1" aria-label="Enter your billing address" class="border-t border-slate-100 px-5 py-5 focus:outline-none">
                                    <div class="alert alert-danger mb-4" role="alert" x-show="formError" x-text="formError" x-cloak></div>

                                    <form x-ref="billingForm" @submit.prevent="continueToPayment()" class="space-y-4" novalidate>
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

                                        <div class="flex items-start gap-3 border-t border-slate-100 pt-4">
                                            <input type="checkbox" id="co_terms" name="terms" value="1" class="mt-1 h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500" aria-describedby="co_terms_err">
                                            <label for="co_terms" class="text-sm text-slate-600">
                                                I agree to the <a href="{{ route('legal.terms') }}" class="font-medium text-primary-600 hover:underline">Terms of Use</a>,
                                                <a href="{{ route('legal.refund') }}" class="font-medium text-primary-600 hover:underline">Refund Policy</a>, and understand that
                                                domain &amp; hosting renew after the first year (<a href="{{ route('legal.renewal') }}" class="font-medium text-primary-600 hover:underline">Renewal Policy</a>).
                                            </label>
                                        </div>
                                        <p id="co_terms_err" class="field-error" role="alert" x-show="fieldError('terms')" x-text="fieldError('terms')" x-cloak></p>

                                        <div class="flex items-center justify-between pt-2">
                                            <button type="button" @click="back()" class="btn-secondary">Back</button>
                                            <button type="submit" class="btn-primary" x-bind:disabled="initialising || paying">
                                                <span x-show="!initialising && !paying">
                                                    <span x-show="!isFree">Continue to payment</span>
                                                    <span x-show="isFree" x-cloak>Complete order</span>
                                                </span>
                                                <span x-show="initialising || paying" x-cloak class="inline-flex items-center gap-2">
                                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/></svg>
                                                    <span x-text="payStatus || (isFree ? 'Completing your order…' : 'Starting secure payment…')"></span>
                                                </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Step 4: Payment method --}}
                            <div class="rounded-[14px] border bg-white shadow-soft transition"
                                 x-bind:class="isActive('payment') ? 'border-primary-500' : 'border-slate-200'">
                                <div class="flex w-full items-center gap-3 px-5 py-4 text-left">
                                    <span class="grid h-7 w-7 flex-shrink-0 place-items-center rounded-full text-sm font-bold"
                                          x-bind:class="isActive('payment') ? 'bg-primary-500 text-white' : 'bg-slate-100 text-slate-500'">{{ $stepNo['payment'] }}</span>
                                    <h2 data-step-heading class="flex-1 text-base font-bold focus:outline-none">Choose a payment method</h2>
                                </div>

                                <div data-step="payment" x-show="isActive('payment')" x-cloak tabindex="-1" aria-label="Choose a payment method" class="border-t border-slate-100 px-5 py-5 focus:outline-none">
                                    <p class="text-sm text-slate-600">Paying for order <span class="font-semibold text-slate-700" x-text="orderNumber"></span>. Enter your card details below.</p>
                                    <div class="alert alert-danger my-4" role="alert" x-show="formError" x-text="formError" x-cloak></div>

                                    {{-- Stripe Payment Element mounts here --}}
                                    <div x-ref="paymentElement" class="mt-4 min-h-[44px]" aria-label="Card details"></div>

                                    <div class="mt-5 flex items-center justify-between">
                                        <button type="button" @click="back()" class="btn-secondary" x-bind:disabled="paying">Back</button>
                                        <p class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            Secured by Stripe
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <noscript>
                                <div class="alert alert-warning" role="alert">JavaScript is required to complete payment securely. Please enable JavaScript and reload this page.</div>
                            </noscript>
                        </div>

                        <aside class="lg:col-span-1" aria-label="Order summary">
                            @include('checkout.partials.summary', ['showPay' => true, 'lineItems' => $lineItems, 'total' => $total, 'freeYearNotice' => $freeYearNotice])
                        </aside>
                    </div>
            @endguest
        </div>
    </section>
@endsection
