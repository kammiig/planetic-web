@extends('layouts.public')

@section('title', 'Premium Hosting, Domains & Bespoke Websites')

@section('content')
    {{-- Hero --}}
    <section class="hero-gradient text-white">
        <div class="container-px grid gap-12 py-16 lg:grid-cols-2 lg:py-24">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-accent-cyan">Premium Hosting, Domains &amp; Websites</p>
                <h1 class="mt-3 text-4xl font-extrabold leading-tight sm:text-5xl">
                    Your website, domain &amp; hosting — done for you.
                </h1>
                <p class="mt-4 max-w-xl text-lg text-slate-300">
                    Search a domain, choose a plan, and let Planetic Web register, host and configure everything automatically. Secure billing, DNS and support in one dashboard.
                </p>

                <div class="mt-8 rounded-[18px] bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur">
                    <x-domain-search variant="hero" :autofocus="false" />
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('website-package') }}" class="btn-primary">Get a Website for £200</a>
                    <a href="{{ route('hosting.index') }}" class="btn-ghost-dark">View Hosting Plans</a>
                </div>
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
                </div>
            </div>
        </div>
    </section>

    {{-- Trust badges --}}
    <section class="border-b border-slate-200 bg-slate-50" aria-label="Trust indicators">
        <div class="container-px flex flex-wrap items-center justify-center gap-x-10 gap-y-4 py-8 text-sm font-semibold text-slate-600">
            <span class="flex items-center gap-2"><svg class="h-5 w-5 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Free SSL on every site</span>
            <span class="flex items-center gap-2"><svg class="h-5 w-5 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2 4 6v6c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6z"/></svg> Cloudflare protected</span>
            <span class="flex items-center gap-2"><svg class="h-5 w-5 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20"/></svg> cPanel hosting</span>
            <span class="flex items-center gap-2"><svg class="h-5 w-5 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg> Secure Stripe billing</span>
        </div>
    </section>

    {{-- Services overview --}}
    <section class="container-px py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold">Everything you need to get online</h2>
            <p class="mt-3 text-slate-600">One platform for domains, hosting, DNS and a bespoke website — fully managed.</p>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Domains', 'Search and register your domain with WHOIS privacy and automatic Cloudflare DNS.', route('domains.index'), 'M3 12h18M12 3a15 15 0 0 1 0 18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z'],
                ['Hosting', 'Fast, secure cPanel hosting with free SSL — provisioned automatically.', route('hosting.index'), 'M3 5h18v6H3zM3 13h18v6H3zM7 8h.01M7 16h.01'],
                ['£200 Website', 'A complete bespoke website with free domain and hosting for the first year.', route('website-package'), 'M4 4h16v12H4zM2 20h20'],
            ] as [$title, $desc, $href, $icon])
                <a href="{{ $href }}" class="card transition hover:shadow-large">
                    <span class="grid h-11 w-11 place-items-center rounded-[12px] bg-primary-50 text-primary-600" aria-hidden="true">
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

    {{-- Hosting plans preview --}}
    @if ($hostingPlans->isNotEmpty())
        <section class="bg-slate-50 py-16" x-data="{ cycle: 'monthly' }">
            <div class="container-px">
                <div class="flex flex-col items-center gap-4 text-center">
                    <h2 class="text-3xl font-bold">Simple, transparent hosting</h2>
                    <p class="max-w-xl text-slate-600">Choose a plan that fits. Switch or upgrade any time.</p>
                    <div class="inline-flex rounded-full border border-slate-200 bg-white p-1" role="group" aria-label="Billing cycle">
                        <button type="button" @click="cycle = 'monthly'" :class="cycle === 'monthly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-4 py-2 text-sm font-semibold">Monthly</button>
                        <button type="button" @click="cycle = 'yearly'" :class="cycle === 'yearly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-4 py-2 text-sm font-semibold">Yearly</button>
                    </div>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($hostingPlans as $i => $plan)
                        <x-hosting-plan-card :product="$plan" :recommended="$i === 1" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- £200 website package --}}
    <section class="container-px py-16">
        <div class="overflow-hidden rounded-[24px] hero-gradient text-white">
            <div class="grid gap-8 p-8 md:grid-cols-2 md:p-12">
                <div>
                    <span class="badge badge-primary">Most popular</span>
                    <h2 class="mt-3 text-3xl font-bold">Complete Bespoke Website for £{{ number_format($websitePackagePrice, 0) }}</h2>
                    <p class="mt-3 text-lg font-medium text-accent-cyan">{{ $freeYearNotice }}</p>
                    <ul class="mt-6 space-y-2 text-slate-200">
                        @foreach (['Custom design built for your business', 'Free domain for the first year', 'Free hosting for the first year', 'SSL, DNS and email set up for you', 'Mobile-friendly and fast'] as $point)
                            <li class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>{{ $point }}
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

    {{-- FAQ --}}
    <section class="bg-slate-50 py-16">
        <div class="container-px mx-auto max-w-3xl">
            <h2 class="text-center text-3xl font-bold">Frequently asked questions</h2>
            <div class="mt-8 space-y-3" x-data="{ open: null }">
                @foreach ([
                    ['Is the domain and hosting really free?', 'Yes — with the £200 website package your domain and hosting are free for the first year. Renewal applies after the first year at standard rates.'],
                    ['How fast will my website be ready?', 'Once you complete the short intake form and provide your content, our team begins straight away. Most sites are ready within a couple of weeks.'],
                    ['Do you set up email and SSL?', 'Yes. We configure SSL, DNS and email records (SPF, DKIM, DMARC) for you automatically through Cloudflare and cPanel.'],
                    ['Can I pay monthly for hosting?', 'Yes. Hosting plans are available monthly or yearly, and you can upgrade at any time.'],
                ] as $i => [$q, $a])
                    <div class="card-dash">
                        <h3>
                            <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                    class="flex w-full items-center justify-between gap-4 text-left text-base font-semibold text-slate-900"
                                    :aria-expanded="open === {{ $i }}" aria-controls="faq-{{ $i }}">
                                {{ $q }}
                                <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </h3>
                        <div id="faq-{{ $i }}" x-show="open === {{ $i }}" x-collapse x-cloak class="mt-3 text-sm text-slate-600">{{ $a }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="container-px py-16 text-center">
        <h2 class="text-3xl font-bold">Ready to get online?</h2>
        <p class="mt-3 text-slate-600">Search your domain or start your £200 website today.</p>
        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('domains.index') }}" class="btn-primary">Search a domain</a>
            <a href="{{ route('website-package') }}" class="btn-secondary">Get a website</a>
        </div>
    </section>
@endsection
