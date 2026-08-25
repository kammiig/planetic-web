@extends('layouts.public')

@section('title', 'Domain Name Registration '.region()->shortName().' — .'.region()->defaultTld().($cheapestTld ? ' from '.money($cheapestTld).'/yr' : ''))
@section('meta_description', 'Register a domain name with free WHOIS privacy and automatic Cloudflare DNS.'.($cheapestTld ? ' Domains from '.money($cheapestTld).'/yr.' : '').($websitePackagePrice ? ' Free with our '.money_compact($websitePackagePrice).' website package.' : ''))

@section('content')
    <div
        x-data="domainSearch(@js([
            'searchUrl' => region()->route('domains.search'),
            'cartUrl' => region()->route('cart.items.store'),
            'cartIndexUrl' => region()->route('cart.index'),
            'websitePackagePrice' => $websitePackagePrice,
            'symbol' => region()->symbol(),
            'initialQuery' => request('q', ''),
        ]))"
        x-init="if (query) search()"
    >
        {{-- ===================== Search bar ===================== --}}
        <section class="border-b border-slate-200 bg-white">
            <div class="container-px py-10 sm:py-12">
                <div class="mx-auto max-w-3xl text-center">
                    <h1 class="text-3xl font-extrabold sm:text-4xl">{{ setting('domains.title', 'Find your perfect domain name') }}</h1>
                    <p class="mt-2 text-slate-600">{{ setting('domains.subtitle', 'Search, register and manage your domain — DNS and SSL set up automatically.') }}</p>
                </div>

                <form @submit.prevent="search" role="search" aria-label="Domain availability search"
                      class="mx-auto mt-6 flex max-w-2xl flex-col gap-2 sm:flex-row">
                    <label for="domain-q" class="sr-only">Domain name</label>
                    <input id="domain-q" type="text" x-model="query" inputmode="url" autocomplete="off" spellcheck="false"
                           placeholder="yourbusiness.com"
                           class="input flex-1 sm:h-[52px] sm:text-lg"
                           aria-describedby="domain-q-help">
                    <button type="submit" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-[10px] bg-slate-900 px-6 py-3 text-base font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400 sm:h-[52px]"
                            x-bind:disabled="loading">
                        <span x-show="!loading">Search domains</span>
                        <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/></svg>
                            Searching…
                        </span>
                    </button>
                </form>
                <p id="domain-q-help" class="mx-auto mt-2 max-w-2xl text-center text-sm text-slate-500">Domains register for one year and renew annually. Renewal applies after the first year.</p>
            </div>
        </section>

        {{-- ===================== Results ===================== --}}
        <section class="container-px py-8 sm:py-10" aria-live="polite">
            <div class="mx-auto max-w-4xl">
                {{-- Loading skeleton --}}
                <template x-if="loading">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="h-48 animate-pulse rounded-[16px] bg-slate-100"></div>
                        <div class="h-48 animate-pulse rounded-[16px] bg-slate-100"></div>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="error && !loading">
                    <div class="alert alert-danger flex items-start gap-2" role="alert">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        <span x-text="error"></span>
                    </div>
                </template>

                {{-- Result --}}
                <template x-if="result && !loading">
                    <div>
                        {{-- Availability banner --}}
                        <p class="mb-5 flex items-center gap-2 text-base font-semibold">
                            <template x-if="result.available">
                                <span class="flex items-center gap-2 text-success">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span><span x-text="result.domain"></span> is available!</span>
                                </span>
                            </template>
                            <template x-if="!result.available">
                                <span class="flex items-center gap-2 text-slate-700">
                                    <svg class="h-5 w-5 text-danger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                                    <span><span x-text="result.domain"></span> is already taken — try an option below.</span>
                                </span>
                            </template>
                        </p>

                        <div class="grid gap-4 md:grid-cols-2">
                            {{-- Exact match card --}}
                            <div class="flex flex-col rounded-[16px] border border-slate-200 bg-white p-5 shadow-soft"
                                 x-bind:class="result.available ? '' : 'opacity-75'">
                                <span class="inline-flex w-fit items-center gap-1 rounded-full bg-success/10 px-2.5 py-1 text-xs font-bold text-success">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17l6.2 4-1.7-7.1L22 9.2l-7.2-.6z"/></svg>
                                    Exact match
                                </span>
                                <p class="mt-3 break-all text-xl font-extrabold text-slate-900" x-text="result.domain"></p>
                                <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> The exact name you searched for</li>
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Free WHOIS privacy &amp; auto-renew</li>
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Automatic Cloudflare DNS &amp; SSL</li>
                                </ul>
                                <div class="mt-auto flex items-end justify-between pt-4">
                                    <div>
                                        <template x-if="result.available">
                                            <p><span class="text-2xl font-extrabold text-slate-900"><span x-text="result.symbol || symbol"></span><span x-text="result.price"></span></span><span class="text-sm text-slate-500">/yr</span></p>
                                        </template>
                                        <template x-if="result.available"><p class="text-xs text-slate-500">For the first year</p></template>
                                        <template x-if="!result.available"><p class="text-sm text-slate-500">Not available</p></template>
                                    </div>
                                    <template x-if="result.available">
                                        <button type="button" class="btn-primary btn-sm" @click="add('domain_registration', result.domain, result.domain)" x-bind:disabled="adding === result.domain">
                                            <span x-show="adding !== result.domain">Get domain</span>
                                            <span x-show="adding === result.domain" x-cloak>Adding…</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Best value bundle card --}}
                            <div class="flex flex-col rounded-[16px] border-2 border-primary-500 bg-white p-5 shadow-soft">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex w-fit items-center gap-1 rounded-full bg-primary-500 px-2.5 py-1 text-xs font-bold text-white">Best value</span>
                                    <a href="{{ region()->route('website-package') }}" class="text-sm font-medium text-primary-600 hover:underline">Learn more</a>
                                </div>
                                <p class="mt-3 break-words text-lg font-extrabold text-slate-900"><span x-text="bundleDomain"></span> + Website &amp; hosting</p>
                                <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Free domain for the first year</li>
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Managed cloud hosting included</li>
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> A complete bespoke website, built for you</li>
                                </ul>
                                <div class="mt-auto flex items-end justify-between pt-4">
                                    <div>
                                        <p><span class="text-2xl font-extrabold text-slate-900">{{ money_compact($websitePackagePrice) }}</span></p>
                                        <p class="text-xs text-slate-500">Free domain included</p>
                                    </div>
                                    <button type="button" class="btn-primary btn-sm" @click="add('website_package', bundleDomain, 'bundle')" x-bind:disabled="adding === 'bundle'">
                                        <span x-show="adding !== 'bundle'">Get domain + website</span>
                                        <span x-show="adding === 'bundle'" x-cloak>Adding…</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ===================== More options ===================== --}}
                        {{-- Alternative extensions for the same name. Shown for
                             an available domain too, not just a taken one — a
                             customer who can have example.com usually still
                             wants .co.uk and .net beside it. --}}
                        <template x-if="alternatives.length">
                            <div class="mt-12">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h2 class="text-lg font-bold text-slate-900">More options</h2>
                                    <label class="flex items-center gap-2">
                                        <span class="sr-only">Sort alternative domains by</span>
                                        <select x-model="sort"
                                                class="h-10 cursor-pointer rounded-[10px] border border-slate-300 bg-white px-3 pr-8 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-400 focus:border-primary-500 focus:outline-none focus:ring-4 focus:ring-primary-500/15">
                                            <option value="popularity">Popularity</option>
                                            <option value="price-asc">Price: low to high</option>
                                            <option value="price-desc">Price: high to low</option>
                                            <option value="name">Name: A–Z</option>
                                        </select>
                                    </label>
                                </div>

                                <ul class="mt-4 divide-y divide-slate-200 overflow-hidden rounded-[16px] border border-slate-200 bg-white shadow-soft">
                                    <template x-for="alt in sortedAlternatives" :key="alt.domain">
                                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 transition hover:bg-slate-50 sm:gap-6">
                                            <span class="min-w-0 flex-1 break-all text-base font-semibold text-slate-900" x-text="alt.domain"></span>
                                            <div class="flex items-center gap-4 sm:gap-6">
                                                <span class="whitespace-nowrap text-right">
                                                    <span class="text-lg font-extrabold text-slate-900"><span x-text="alt.symbol || symbol"></span><span x-text="alt.price"></span></span>
                                                    <span class="block text-xs text-slate-500">For the first year</span>
                                                </span>
                                                <button type="button"
                                                        class="min-w-[76px] rounded-[10px] border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:border-slate-900 hover:bg-slate-900 hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
                                                        @click="add('domain_registration', alt.domain, alt.domain)"
                                                        x-bind:disabled="adding === alt.domain">
                                                    <span x-show="adding !== alt.domain">Get</span>
                                                    <span x-show="adding === alt.domain" x-cloak>Adding…</span>
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ul>

                                <p class="mt-3 text-xs text-slate-500">All prices are for the first year. Domains renew annually at our standard rates — see the <a href="{{ region()->route('legal.renewal') }}" class="font-medium underline">Renewal Policy</a>.</p>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Empty / initial state --}}
                <template x-if="!result && !loading && !error">
                    <div class="text-center">
                        <p class="text-slate-600">Start by searching for your business name above.</p>
                        @php $chips = $featuredTlds->isNotEmpty() ? $featuredTlds : $tldPrices->take(8); @endphp
                        @if ($chips->isNotEmpty())
                            <div class="mx-auto mt-5 flex max-w-2xl flex-wrap justify-center gap-2">
                                @foreach ($chips as $tld)
                                    <span class="badge badge-neutral text-sm">.{{ ltrim($tld->tld, '.') }} <span class="font-bold text-primary-600">{{ money($tld->registerPrice()) }}/yr</span></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        </section>

        {{-- ===================== Trust / reviews ===================== --}}
        {{-- Identical component and data as the home page, so a testimonial
             edited in the admin updates both pages at once. --}}
        <x-testimonials-section :testimonials="$testimonials" class="bg-slate-50 section" />

        {{-- ===================== Renewals, in the open ===================== --}}
        {{-- This used to be a lone alert box floating above the footer. The page
             promises "renewal applies after the first year" without ever saying
             how much, which is the one thing a customer actually wants to know
             — so the closing section now answers it with the real price book. --}}
        @php
            // Only extensions this storefront prices in BOTH years can appear:
            // quoting a first-year price with a blank renewal is exactly the
            // vagueness this section exists to remove.
            $renewalRows = ($featuredTlds->isNotEmpty() ? $featuredTlds : $tldPrices)
                ->filter(fn ($tld) => $tld->registerPrice() !== null && $tld->renewPrice() !== null)
                ->take(5);
        @endphp

        <section class="container-px section">
            <div class="mx-auto max-w-5xl">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="eyebrow">No surprises</p>
                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Straight talk on renewals</h2>
                    <p class="mt-3 text-slate-600">
                        Every domain registers for one year. Here is exactly what it includes and exactly what it costs
                        when it renews — no introductory pricing that quietly triples later.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    {{-- What you get, every time --}}
                    <div class="card">
                        <h3 class="text-lg font-bold text-slate-900">Included with every domain</h3>
                        <ul class="mt-5 space-y-5 text-sm text-slate-700">
                            @foreach ([
                                ['Free WHOIS privacy', 'Your name, address and email stay out of the public WHOIS record.'],
                                ['Automatic Cloudflare DNS &amp; SSL', 'We create the zone, point the nameservers and issue the certificate for you.'],
                                ['Auto-renew, on by default', 'Nothing expires because a reminder went to spam — and you can switch it off any time.'],
                                ['Managed from your dashboard', 'DNS records, contact details and renewals in one place, with support if you would rather we did it.'],
                            ] as [$title, $detail])
                                <li class="flex items-start gap-3">
                                    <span class="feature-check" aria-hidden="true">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>
                                    <span>
                                        <span class="block font-semibold text-slate-900">{!! $title !!}</span>
                                        <span class="mt-0.5 block text-slate-600">{{ $detail }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- The actual renewal figures --}}
                    <div class="card flex flex-col">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="text-lg font-bold text-slate-900">What renewal costs</h3>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Per year, {{ region()->currency() }}</span>
                        </div>

                        @if ($renewalRows->isNotEmpty())
                            <div class="mt-5 overflow-x-auto">
                                <table class="w-full text-sm">
                                    <caption class="sr-only">Domain registration and renewal prices by extension</caption>
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <th scope="col" class="pb-2">Extension</th>
                                            <th scope="col" class="pb-2 text-right">First year</th>
                                            <th scope="col" class="pb-2 text-right">Renews at</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($renewalRows as $tld)
                                            <tr class="border-t border-slate-100">
                                                <th scope="row" class="py-2.5 text-left font-bold text-slate-900">.{{ ltrim($tld->tld, '.') }}</th>
                                                <td class="py-2.5 text-right font-semibold text-slate-900">{{ money($tld->registerPrice()) }}</td>
                                                <td class="py-2.5 text-right text-slate-600">{{ money($tld->renewPrice()) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <p class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-600">
                            We email you well before any renewal date, so a charge never arrives unannounced. Full terms
                            are in the <a href="{{ region()->route('legal.renewal') }}" class="font-semibold text-primary-600 hover:underline">Renewal Policy</a>.
                        </p>

                    </div>
                </div>

                <div class="mt-8 text-center">
                    <a href="#domain-q" class="btn-primary">Search a domain</a>
                </div>
            </div>
        </section>
    </div>
@endsection
