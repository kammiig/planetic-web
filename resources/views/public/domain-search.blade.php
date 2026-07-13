@extends('layouts.public')

@section('title', 'Domain Name Registration UK — .co.uk from £8.99/yr')
@section('meta_description', 'Register a UK domain name with free WHOIS privacy and automatic Cloudflare DNS. .co.uk from £8.99/yr, .com from £12.99/yr — or free with our £200 website package.')

@section('content')
    <div
        x-data="domainSearch(@js([
            'searchUrl' => route('domains.search'),
            'cartUrl' => route('cart.items.store'),
            'cartIndexUrl' => route('cart.index'),
            'websitePackagePrice' => $websitePackagePrice,
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
                                            <p><span class="text-2xl font-extrabold text-slate-900">£<span x-text="result.price"></span></span><span class="text-sm text-slate-500">/yr</span></p>
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
                                    <a href="{{ route('website-package') }}" class="text-sm font-medium text-primary-600 hover:underline">Learn more</a>
                                </div>
                                <p class="mt-3 break-words text-lg font-extrabold text-slate-900"><span x-text="bundleDomain"></span> + Website &amp; hosting</p>
                                <ul class="mt-3 space-y-1.5 text-sm text-slate-600">
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Free domain for the first year</li>
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Managed cloud hosting included</li>
                                    <li class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> A complete bespoke website, built for you</li>
                                </ul>
                                <div class="mt-auto flex items-end justify-between pt-4">
                                    <div>
                                        <p><span class="text-2xl font-extrabold text-slate-900">£{{ number_format($websitePackagePrice, 0) }}</span></p>
                                        <p class="text-xs text-slate-500">Free domain included</p>
                                    </div>
                                    <button type="button" class="btn-primary btn-sm" @click="add('website_package', bundleDomain, 'bundle')" x-bind:disabled="adding === 'bundle'">
                                        <span x-show="adding !== 'bundle'">Get domain + website</span>
                                        <span x-show="adding === 'bundle'" x-cloak>Adding…</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- More options --}}
                        <template x-if="alternatives.length">
                            <div class="mt-10">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-bold">More options</h2>
                                    <label class="flex items-center gap-2 text-sm text-slate-500">
                                        <span class="sr-only sm:not-sr-only">Sort by</span>
                                        <select x-model="sort" class="input h-10 w-auto py-1 text-sm">
                                            <option value="popularity">Popularity</option>
                                            <option value="price-asc">Price: low to high</option>
                                            <option value="price-desc">Price: high to low</option>
                                        </select>
                                    </label>
                                </div>

                                <ul class="mt-4 overflow-hidden rounded-[16px] border border-slate-200">
                                    <template x-for="alt in sortedAlternatives" :key="alt.domain">
                                        <li class="flex items-center justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3 last:border-b-0 odd:bg-slate-50/60">
                                            <span class="min-w-0 break-all font-medium text-slate-900" x-text="alt.domain"></span>
                                            <div class="flex items-center gap-4">
                                                <span class="whitespace-nowrap text-right text-sm">
                                                    <span class="font-bold text-slate-900">£<span x-text="alt.price"></span></span>
                                                    <span class="block text-xs text-slate-400">For first year</span>
                                                </span>
                                                <button type="button" class="btn-secondary btn-sm" @click="add('domain_registration', alt.domain, alt.domain)" x-bind:disabled="adding === alt.domain">
                                                    <span x-show="adding !== alt.domain">Get</span>
                                                    <span x-show="adding === alt.domain" x-cloak>Adding…</span>
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
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
                                    <span class="badge badge-neutral text-sm">.{{ ltrim($tld->tld, '.') }} <span class="font-bold text-primary-600">£{{ number_format($tld->register_price, 2) }}/yr</span></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        </section>

        {{-- ===================== Trust / reviews ===================== --}}
        <section class="bg-slate-900 text-white">
            <div class="container-px py-12">
                <div class="mx-auto max-w-4xl text-center">
                    <div class="flex items-center justify-center gap-1 text-warning" aria-hidden="true">
                        @for ($i = 0; $i < 5; $i++)
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17l6.2 4-1.7-7.1L22 9.2l-7.2-.6z"/></svg>
                        @endfor
                    </div>
                    <p class="mt-3 text-lg font-bold">Trusted by businesses across the UK</p>
                    <p class="text-sm text-slate-300">Real people, real support — here's what our customers say.</p>

                    <div class="mt-8 grid gap-4 text-left sm:grid-cols-3">
                        @foreach ([
                            ['Amazing support', 'Genuinely helpful team — they sorted my domain, hosting and email in a day.', 'Ilja M.'],
                            ['Fast and insightful', 'Quick to respond and they fixed my DNS issue without any fuss. Highly recommend.', 'Matt S.'],
                            ['Great from start to finish', 'They built our website and handled everything technical. Brilliant value.', 'John-Mark A.'],
                        ] as [$title, $body, $name])
                            <figure class="rounded-[14px] border border-white/10 bg-white/5 p-5">
                                <div class="flex gap-0.5 text-warning" aria-hidden="true">
                                    @for ($i = 0; $i < 5; $i++)<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17l6.2 4-1.7-7.1L22 9.2l-7.2-.6z"/></svg>@endfor
                                </div>
                                <figcaption class="mt-2 font-semibold">{{ $title }}</figcaption>
                                <blockquote class="mt-1 text-sm text-slate-300">“{{ $body }}”</blockquote>
                                <p class="mt-2 text-xs text-slate-400">— {{ $name }}</p>
                            </figure>
                        @endforeach
                    </div>

                    <div class="mt-8 rounded-[14px] border border-white/10 bg-white/5 p-4 text-sm text-slate-300">
                        About domain renewals: domains register for one year and renew annually. We remind you well before your renewal date — see the
                        <a href="{{ route('legal.renewal') }}" class="font-semibold text-white underline">Renewal Policy</a>.
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
