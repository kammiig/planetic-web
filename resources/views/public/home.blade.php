@extends('layouts.public')

@section('title', 'Premium Hosting, Domains & Bespoke Websites')
@section('meta_description', 'UK domains, fast cPanel hosting, and a complete bespoke website for £200 with a free domain and hosting for the first year — built, secured and managed for you by Planetic Web.')

@section('content')
    {{-- ============================ Hero ============================ --}}
    <section class="relative overflow-hidden hero-aurora text-white">
        <div class="absolute inset-0 hero-grid opacity-60" aria-hidden="true"></div>
        <div class="hero-orb -right-24 -top-24 h-72 w-72 bg-accent-cyan/20" aria-hidden="true"></div>
        <div class="hero-orb -left-32 top-1/3 h-80 w-80 bg-accent-indigo/15" aria-hidden="true"></div>

        <div class="container-px relative grid items-center gap-12 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
            {{-- Left: headline + domain search --}}
            <div class="flex flex-col justify-center">
                <p class="inline-flex items-center gap-2 self-start rounded-full border border-white/15 bg-white/[0.06] px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-accent-cyan backdrop-blur">
                    <span class="badge-dot bg-accent-cyan"></span>
                    {{ setting('hero.eyebrow', 'Domains · Hosting · Bespoke Websites') }}
                </p>

                <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                    {{ setting('hero.title', 'Super fast & secure web hosting') }}
                    <span class="gradient-text block">{{ setting('hero.title_accent', 'built for your business.') }}</span>
                </h1>

                <p class="mt-5 max-w-xl text-lg text-slate-300">
                    {{ setting('hero.subtitle', 'Search a domain, choose a plan, and let Planetic Web register, host and configure everything automatically. Uptime you can trust, support you can reach.') }}
                </p>

                {{-- Domain search panel (reference: dark rounded "Register a Domain Name") --}}
                <div class="mt-8 rounded-[20px] border border-white/10 bg-white/[0.04] p-4 ring-1 ring-white/5 backdrop-blur sm:p-5">
                    <p class="mb-3 text-sm font-semibold text-white">Register a domain name</p>
                    <x-domain-search variant="hero" :autofocus="false" />

                    @if ($featuredTlds->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($featuredTlds as $tld)
                                <a href="{{ route('domains.index') }}" class="tld-chip">
                                    .{{ ltrim($tld->tld, '.') }}
                                    <span class="tld-chip-price">£{{ number_format($tld->register_price, 2) }}/yr</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
                    <div>
                        <dt class="stat-value">{{ setting('stats.uptime', '99.9%') }}</dt>
                        <dd class="stat-label">Uptime SLA</dd>
                    </div>
                    <div>
                        <dt class="stat-value">{{ setting('stats.support', '24/7') }}</dt>
                        <dd class="stat-label">Expert support</dd>
                    </div>
                    <div>
                        <dt class="stat-value">{{ setting('stats.sites', '500+') }}</dt>
                        <dd class="stat-label">Sites launched</dd>
                    </div>
                </dl>
            </div>

            {{-- Right: isometric server / cloud console visual --}}
            <div class="relative flex items-center justify-center">
                <div class="hero-orb bottom-0 left-1/2 h-40 w-64 -translate-x-1/2 bg-accent-sky/30" aria-hidden="true"></div>

                {{-- Stacked "server" plates behind the console for depth --}}
                <div class="absolute right-6 top-2 hidden h-32 w-32 rotate-12 rounded-[20px] bg-gradient-to-br from-accent-sky/30 to-accent-indigo/10 blur-[2px] lg:block" aria-hidden="true"></div>

                <div class="card-console relative w-full max-w-md">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <span class="text-sm font-semibold">Planetic Cloud Console</span>
                        <span class="badge badge-success"><span class="badge-dot"></span> All systems operational</span>
                    </div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Domain</dt><dd class="font-medium">yourbusiness.co.uk</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Hosting</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Active</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">DNS (Cloudflare)</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Proxied</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">SSL</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Secured</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Business email</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Ready</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Next renewal</dt><dd class="font-medium">{{ now()->addYear()->format('j M Y') }}</dd></div>
                    </dl>
                    <div class="mt-5 flex items-center gap-3 border-t border-white/10 pt-4 text-xs text-slate-300">
                        <svg class="h-4 w-4 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M9 11l3 3L22 4"/></svg>
                        Provisioned automatically after checkout — nothing to configure.
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================= Trust badges ======================= --}}
    <section class="border-b border-slate-200 bg-slate-50" aria-label="Trust indicators">
        <div class="container-px flex flex-wrap items-center justify-center gap-x-10 gap-y-4 py-8 text-sm font-semibold text-slate-600">
            @php
                $trustIcons = [
                    'M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM7 11V7a5 5 0 0 1 10 0v4',
                    'M12 2 4 6v6c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6z',
                    'M2 4h20v12H2zM2 9h20',
                    'M22 11.08V12a10 10 0 1 1-5.93-9.14 M9 11l3 3L22 4',
                ];
            @endphp
            @foreach (['trust.badge_1' => 'Free SSL on every site', 'trust.badge_2' => 'Cloudflare protected', 'trust.badge_3' => 'cPanel hosting', 'trust.badge_4' => 'Secure Stripe billing'] as $key => $default)
                <span class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="{{ $trustIcons[$loop->index] }}"/></svg>
                    {{ setting($key, $default) }}
                </span>
            @endforeach
        </div>
    </section>

    {{-- ===================== Three main offers ===================== --}}
    @php
        $businessPkg = $businessHosting?->hostingPackage;
        $businessMonthly = $businessHosting?->priceFor('monthly');
        $businessYearly = $businessHosting?->priceFor('yearly');
        $businessFeatures = $businessPkg
            ? collect($businessPkg->featureList())->take(6)
            : collect(['Free domain for the first year', 'cPanel hosting & free SSL', 'Cloudflare DNS & CDN', 'Business email accounts', 'Free SSL certificate', 'Expert 24/7 support']);
        $websiteFeatures = collect($websitePackage?->features ?: [
            'Free domain & hosting for year one',
            'Cloudflare DNS, CDN & free SSL',
            'cPanel hosting + business emails',
            'Bespoke website designed & built for you',
            'Basic SEO setup to get you found',
            'Stock images & content support',
        ])->take(7);
    @endphp
    <section class="container-px section">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">{{ setting('offers.eyebrow', 'Pick how you want to start') }}</p>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">{{ setting('offers.title', 'Three simple ways to get online') }}</h2>
            <p class="mt-3 text-slate-600">{{ setting('offers.subtitle', 'Just a domain, fully-managed business hosting, or a complete website done for you — all with transparent pricing.') }}</p>
        </div>

        <div class="mt-12 grid items-stretch gap-6 lg:grid-cols-3">
            {{-- Offer 1: Register a domain --}}
            <div class="offer-card">
                <span class="offer-icon" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"/></svg>
                </span>
                <h3 class="mt-4 text-xl font-bold">Register a Domain</h3>
                <p class="mt-1 text-sm text-slate-500">Secure the perfect name for your business.</p>
                <p class="mt-5"><span class="text-3xl font-extrabold text-slate-900">from £{{ number_format($featuredTlds->min('register_price') ?? 9.56, 2) }}</span><span class="text-slate-500">/year</span></p>
                <ul class="mt-5 flex-1 space-y-2.5 text-sm text-slate-600">
                    @foreach (['Instant availability search', 'Free WHOIS privacy', 'Automatic Cloudflare DNS', 'Manage everything in one dashboard'] as $point)
                        <li class="flex items-start gap-2.5">
                            <span class="feature-check" aria-hidden="true"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>{{ $point }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('domains.index') }}" class="btn-secondary mt-6 w-full">Search Domain</a>
            </div>

            {{-- Offer 2: Business hosting (free domain) --}}
            <div class="offer-card border-primary-200" x-data="{ cycle: 'monthly' }">
                <span class="offer-icon" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h18v6H3zM3 13h18v6H3zM7 8h.01M7 16h.01"/></svg>
                </span>
                <h3 class="mt-4 text-xl font-bold">Business Hosting</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $businessPkg?->tagline ?: 'Fast cPanel hosting with a free domain.' }}</p>

                @if ($businessMonthly || $businessYearly)
                    <div class="mt-5">
                        @if ($businessMonthly)
                            <p x-show="cycle === 'monthly'"><span class="text-3xl font-extrabold text-slate-900">£{{ number_format($businessMonthly->amount, 2) }}</span><span class="text-slate-500">/month</span></p>
                        @endif
                        @if ($businessYearly)
                            <p x-show="cycle === 'yearly'" x-cloak><span class="text-3xl font-extrabold text-slate-900">£{{ number_format($businessYearly->amount, 2) }}</span><span class="text-slate-500">/year</span></p>
                        @endif
                        @if ($businessMonthly && $businessYearly)
                            <div class="mt-3 inline-flex rounded-full border border-slate-200 bg-slate-50 p-1 text-xs" role="group" aria-label="Billing cycle">
                                <button type="button" @click="cycle = 'monthly'" :class="cycle === 'monthly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-3 py-1 font-semibold transition">Monthly</button>
                                <button type="button" @click="cycle = 'yearly'" :class="cycle === 'yearly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-3 py-1 font-semibold transition">Yearly</button>
                            </div>
                        @endif
                    </div>
                @endif

                <ul class="mt-5 flex-1 space-y-2.5 text-sm text-slate-600">
                    <li class="flex items-start gap-2.5">
                        <span class="feature-check" aria-hidden="true"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <span class="font-semibold text-slate-800">Free domain for the first year</span>
                    </li>
                    @foreach ($businessFeatures as $point)
                        <li class="flex items-start gap-2.5">
                            <span class="feature-check" aria-hidden="true"><svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg></span>{{ $point }}
                        </li>
                    @endforeach
                </ul>

                @if ($businessHosting)
                    <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="item_type" value="hosting">
                        <input type="hidden" name="product_id" value="{{ $businessHosting->id }}">
                        <input type="hidden" name="billing_cycle" :value="cycle" value="monthly">
                        <button type="submit" class="btn-primary w-full">Choose Business Hosting</button>
                    </form>
                @else
                    <a href="{{ route('hosting.index') }}" class="btn-primary mt-6 w-full">Choose Business Hosting</a>
                @endif
            </div>

            {{-- Offer 3: Complete bespoke website (featured) --}}
            <div class="offer-card-featured">
                <span class="offer-ribbon">★ Best value</span>
                <span class="offer-icon-dark" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v12H4zM2 20h20M9 8h6M9 12h3"/></svg>
                </span>
                <h3 class="mt-4 text-xl font-bold text-white">Complete Bespoke Website</h3>
                <p class="mt-1 text-sm text-slate-300">Everything done for you — domain, hosting & website.</p>
                <p class="mt-5"><span class="text-4xl font-extrabold text-white">£{{ number_format($websitePackagePrice, 0) }}</span><span class="text-slate-300"> one-time</span></p>
                <p class="mt-1 text-sm font-medium text-accent-cyan">{{ $freeYearNotice }}</p>

                <ul class="mt-5 flex-1 space-y-2.5 text-sm text-slate-200">
                    @foreach ($websiteFeatures as $point)
                        <li class="flex items-start gap-2.5">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $point }}
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('website-package') }}" class="btn-primary mt-6 w-full">Get Complete Website</a>
            </div>
        </div>
    </section>

    {{-- ===================== Services overview ===================== --}}
    <section class="bg-slate-50 section">
        <div class="container-px">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ setting('services.title', 'Everything you need to get online') }}</h2>
                <p class="mt-3 text-slate-600">{{ setting('services.subtitle', 'One platform for domains, hosting, DNS and a bespoke website — fully managed.') }}</p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Domains', 'Search and register your domain with WHOIS privacy and automatic Cloudflare DNS.', route('domains.index'), 'M3 12h18M12 3a15 15 0 0 1 0 18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z'],
                    ['Hosting', 'Fast, secure cPanel hosting with free SSL — provisioned automatically.', route('hosting.index'), 'M3 5h18v6H3zM3 13h18v6H3zM7 8h.01M7 16h.01'],
                    ['£200 Website', 'A complete bespoke website with free domain and hosting for the first year.', route('website-package'), 'M4 4h16v12H4zM2 20h20'],
                ] as [$title, $desc, $href, $icon])
                    <a href="{{ $href }}" class="card lift">
                        <span class="grid h-12 w-12 place-items-center rounded-[14px] bg-primary-50 text-primary-600" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $icon }}"/></svg>
                        </span>
                        <h3 class="mt-4 text-xl font-bold">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $desc }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-primary-600">Learn more
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== Reviews / trust ===================== --}}
    @if ($testimonials->isNotEmpty())
        @php
            $reviewCount = $testimonials->count();
            $avgRating = round($testimonials->avg('rating'), 1);
            $avgStars = (int) round($avgRating);
        @endphp
        <section class="section">
            <div class="container-px">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="eyebrow">{{ setting('testimonials.eyebrow', 'Verified customer reviews') }}</p>
                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">{{ setting('testimonials.title', 'Trusted by businesses across the UK') }}</h2>
                    <div class="mt-5 flex justify-center">
                        <span class="rating-summary">
                            <span class="review-stars" aria-hidden="true">{!! str_repeat('★', $avgStars).str_repeat('☆', 5 - $avgStars) !!}</span>
                            <span class="text-sm font-bold text-slate-900">{{ number_format($avgRating, 1) }} out of 5</span>
                            <span class="text-sm text-slate-500">· based on {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
                        </span>
                    </div>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $t)
                        <x-review-card :testimonial="$t" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== FAQ ===================== --}}
    @if ($faqs->isNotEmpty())
        @php
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f->question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer],
                ])->values()->all(),
            ];
        @endphp
        @push('head')
            <script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        @endpush

        <section class="bg-slate-50 section">
            <div class="container-px mx-auto max-w-3xl">
                <h2 class="text-center text-3xl font-bold sm:text-4xl">{{ setting('faq.title', 'Frequently asked questions') }}</h2>
                <div class="mt-10 space-y-3" x-data="{ open: null }">
                    @foreach ($faqs as $i => $faq)
                        <div class="card-dash">
                            <h3>
                                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                        class="flex w-full items-center justify-between gap-4 text-left text-base font-semibold text-slate-900"
                                        :aria-expanded="open === {{ $i }}" aria-controls="faq-{{ $i }}">
                                    {{ $faq->question }}
                                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </h3>
                            <div id="faq-{{ $i }}" x-show="open === {{ $i }}" x-collapse x-cloak class="mt-3 text-sm text-slate-600">{{ $faq->answer }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== Final CTA ===================== --}}
    <section class="container-px section">
        <div class="relative mx-auto max-w-4xl overflow-hidden rounded-[24px] hero-aurora p-10 text-center text-white sm:p-14">
            <div class="absolute inset-0 hero-grid opacity-50" aria-hidden="true"></div>
            <div class="relative">
                <h2 class="text-3xl font-bold sm:text-4xl">{{ setting('cta.title', 'Ready to get online?') }}</h2>
                <p class="mt-3 text-slate-300">{{ setting('cta.subtitle', 'Search your domain or start your £200 complete website today.') }}</p>
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('domains.index') }}" class="btn-primary">Search a domain</a>
                    <a href="{{ route('website-package') }}" class="btn-ghost-dark">Get a website</a>
                </div>
            </div>
        </div>
    </section>
@endsection
