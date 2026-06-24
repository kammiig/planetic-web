@extends('layouts.public')

@section('title', 'Premium Hosting, Domains & Bespoke Websites')
@section('meta_description', 'UK domains, fast cPanel hosting, and a complete bespoke website for £200 with a free domain and hosting for the first year — built, secured and managed for you by Planetic Web.')

@section('content')
    {{-- ============================ Hero ============================ --}}
    <section class="relative overflow-hidden hero-gradient text-white">
        <div class="absolute inset-0 hero-grid opacity-70" aria-hidden="true"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent-cyan/20 blur-3xl" aria-hidden="true"></div>
        <div class="container-px relative grid gap-12 py-16 lg:grid-cols-2 lg:py-24">
            <div class="flex flex-col justify-center">
                <p class="eyebrow">{{ setting('hero.eyebrow', 'Premium Hosting, Domains & Websites') }}</p>
                <h1 class="mt-3 text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                    {{ setting('hero.title', 'Your website, domain & hosting — done for you.') }}
                </h1>
                <p class="mt-5 max-w-xl text-lg text-slate-300">
                    {{ setting('hero.subtitle', 'Search a domain, choose a plan, and let Planetic Web register, host and configure everything automatically.') }}
                </p>

                <div class="mt-8 rounded-[18px] bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur">
                    <x-domain-search variant="hero" :autofocus="false" />
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('website-package') }}" class="btn-primary">{{ setting('hero.cta_primary', 'Get a Website for £200') }}</a>
                    <a href="{{ route('hosting.index') }}" class="btn-ghost-dark">{{ setting('hero.cta_secondary', 'View Hosting Plans') }}</a>
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

            {{-- Cloud console preview card --}}
            <div class="flex items-center justify-center">
                <div class="card-console w-full max-w-md">
                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                        <span class="text-sm font-semibold">Planetic Cloud Console</span>
                        <span class="badge badge-success"><span class="badge-dot"></span> All systems operational</span>
                    </div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Domain</dt><dd class="font-medium">yourbusiness.co.uk</dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Hosting</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Active</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">DNS (Cloudflare)</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Proxied</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">SSL</dt><dd><span class="badge badge-success"><span class="badge-dot"></span> Secured</span></dd></div>
                        <div class="flex items-center justify-between"><dt class="text-slate-400">Next renewal</dt><dd class="font-medium">{{ now()->addYear()->format('j M Y') }}</dd></div>
                    </dl>
                    @if ($featuredTlds->isNotEmpty())
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-white/10 pt-4">
                            @foreach ($featuredTlds as $tld)
                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">.{{ ltrim($tld->tld, '.') }} <span class="text-accent-cyan">£{{ number_format($tld->register_price, 2) }}</span></span>
                            @endforeach
                        </div>
                    @endif
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

    {{-- ===================== Services overview ===================== --}}
    <section class="container-px section">
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
    </section>

    {{-- ===================== Hosting plans ===================== --}}
    @if ($hostingPlans->isNotEmpty())
        <section class="bg-slate-50 section" x-data="{ cycle: 'monthly' }">
            <div class="container-px">
                <div class="flex flex-col items-center gap-4 text-center">
                    <h2 class="text-3xl font-bold sm:text-4xl">{{ setting('hosting.title', 'Simple, transparent hosting') }}</h2>
                    <p class="max-w-xl text-slate-600">{{ setting('hosting.subtitle', 'Choose a plan that fits. Switch or upgrade any time.') }}</p>
                    <div class="inline-flex rounded-full border border-slate-200 bg-white p-1" role="group" aria-label="Billing cycle">
                        <button type="button" @click="cycle = 'monthly'" :class="cycle === 'monthly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-5 py-2 text-sm font-semibold transition">Monthly</button>
                        <button type="button" @click="cycle = 'yearly'" :class="cycle === 'yearly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-5 py-2 text-sm font-semibold transition">Yearly</button>
                    </div>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($hostingPlans as $plan)
                        <x-hosting-plan-card :product="$plan" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== £200 website package ===================== --}}
    <section class="container-px section">
        <div class="relative overflow-hidden rounded-[24px] hero-gradient text-white">
            <div class="absolute inset-0 hero-grid opacity-60" aria-hidden="true"></div>
            <div class="relative grid gap-8 p-8 md:grid-cols-2 md:p-12">
                <div>
                    <span class="badge badge-primary">Most popular</span>
                    <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Complete Bespoke Website for £{{ number_format($websitePackagePrice, 0) }}</h2>
                    <p class="mt-3 text-lg font-medium text-accent-cyan">{{ $freeYearNotice }}</p>
                    <ul class="mt-6 space-y-2.5 text-slate-200">
                        @foreach (($websitePackage?->features ?: ['Custom design built for your business', 'Free domain for the first year', 'Free hosting for the first year', 'SSL, DNS and email set up for you', 'Mobile-friendly and fast']) as $point)
                            <li class="flex items-center gap-2.5">
                                <svg class="h-5 w-5 flex-shrink-0 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $point }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('website-package') }}" class="btn-primary mt-8">Start your website</a>
                </div>
                <div class="flex items-center justify-center">
                    <div class="rounded-[18px] bg-white/10 p-8 text-center ring-1 ring-white/15">
                        <p class="text-sm uppercase tracking-wide text-slate-300">One-time</p>
                        <p class="mt-2 text-6xl font-extrabold">£{{ number_format($websitePackagePrice, 0) }}</p>
                        <p class="mt-2 text-sm text-slate-300">Domain &amp; hosting included for year one</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== Testimonials ===================== --}}
    @if ($testimonials->isNotEmpty())
        <section class="bg-slate-50 section">
            <div class="container-px">
                <h2 class="text-center text-3xl font-bold sm:text-4xl">{{ setting('testimonials.title', 'Trusted by businesses across the UK') }}</h2>
                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ($testimonials as $t)
                        <figure class="card flex h-full flex-col">
                            <div class="stars" aria-label="{{ $t->rating }} out of 5 stars">{!! str_repeat('★', (int) $t->rating).str_repeat('☆', 5 - (int) $t->rating) !!}</div>
                            <blockquote class="mt-4 flex-1 text-slate-700">“{{ $t->body }}”</blockquote>
                            <figcaption class="mt-5 flex items-center gap-3">
                                @if ($t->avatar_url)
                                    <img src="{{ $t->avatar_url }}" alt="{{ $t->author_name }}" class="h-10 w-10 rounded-full object-cover" loading="lazy">
                                @else
                                    <span class="grid h-10 w-10 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700" aria-hidden="true">{{ $t->initials() }}</span>
                                @endif
                                <span>
                                    <span class="block text-sm font-bold text-slate-900">{{ $t->author_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ trim(($t->author_role ? $t->author_role.', ' : '').$t->company, ', ') }}</span>
                                </span>
                            </figcaption>
                        </figure>
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

        <section class="section">
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
    <section class="container-px pb-20 text-center">
        <div class="mx-auto max-w-2xl rounded-[24px] border border-slate-200 bg-white p-10 shadow-soft">
            <h2 class="text-3xl font-bold sm:text-4xl">{{ setting('cta.title', 'Ready to get online?') }}</h2>
            <p class="mt-3 text-slate-600">{{ setting('cta.subtitle', 'Search your domain or start your £200 website today.') }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('domains.index') }}" class="btn-primary">Search a domain</a>
                <a href="{{ route('website-package') }}" class="btn-secondary">Get a website</a>
            </div>
        </div>
    </section>
@endsection
