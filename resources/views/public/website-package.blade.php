@extends('layouts.public')

@section('title', 'Small Business Website Design '.region()->shortName().' — Complete Website for '.money_compact($price))
@section('meta_description', 'How much does a website cost? Ours is '.money_compact($price).' all-in: bespoke small business website design with a free domain and hosting for the first year, SSL and email set up for you. Ready in about two weeks.')

@php
    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'Complete Bespoke Website',
        'description' => 'A complete bespoke business website with a free domain and hosting for the first year, basic SEO and Cloudflare setup, built and managed by Planetic Web.',
        'image' => asset('images/og-default.png'),
        'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
        'offers' => [
            '@type' => 'Offer',
            'price' => number_format((float) ($price ?? 200), 2, '.', ''),
            'priceCurrency' => 'GBP',
            'availability' => 'https://schema.org/InStock',
            'url' => region()->route('website-package'),
        ],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    {{-- Hero offer --}}
    <section class="hero-gradient text-white">
        <div class="container-px py-16 text-center lg:py-20">
            <span class="badge badge-primary">Complete package</span>
            <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-extrabold sm:text-5xl">
                Complete Bespoke Website for {{ money_compact($price) }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg font-medium text-accent-cyan">
                {{ $freeYearNotice }}
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <form method="POST" action="{{ region()->route('cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="item_type" value="website_package">
                    @if ($product)<input type="hidden" name="product_id" value="{{ $product->id }}">@endif
                    <button type="submit" class="btn-primary">Start Now</button>
                </form>
                <a href="{{ region()->route('domains.index') }}" class="btn-ghost-dark">Search a domain first</a>
            </div>
        </div>
    </section>

    {{-- What's included --}}
    <section class="container-px section">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold sm:text-4xl">What's included</h2>
            <p class="mt-3 text-slate-600">{{ $package?->tagline ?: 'Everything you need to launch a professional website.' }}</p>
        </div>
        @php
            $included = $package?->features ?: [
                'Bespoke design built around your brand — not a template',
                'Free domain for the first year (WHOIS privacy included)',
                'Free, fast cPanel hosting with SSL for the first year',
                'Cloudflare DNS plus SPF, DKIM and DMARC email setup',
                'Mobile-friendly, fast-loading pages',
                'Fully managed and launched for you',
            ];
        @endphp
        <div class="mx-auto mt-10 grid max-w-4xl gap-4 sm:grid-cols-2">
            @foreach ($included as $item)
                <div class="card-dash flex items-start gap-3">
                    <span class="feature-check" aria-hidden="true">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <span class="font-medium text-slate-800">{{ $item }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-slate-50 py-16">
        <div class="container-px">
            <h2 class="text-center text-3xl font-bold">How it works</h2>
            <ol class="mt-10 grid gap-6 md:grid-cols-4">
                @foreach ([
                    ['1', 'Choose &amp; pay', 'Pick your domain and pay securely with Stripe.'],
                    ['2', 'Tell us about you', 'Complete a short intake form with your business details and content.'],
                    ['3', 'We build it', 'Our team designs and builds your bespoke website.'],
                    ['4', 'Review &amp; launch', 'You review, we refine, and your site goes live.'],
                ] as [$n, $title, $desc])
                    <li class="card-dash">
                        <span class="grid h-9 w-9 place-items-center rounded-full bg-primary-500 font-bold text-white" aria-hidden="true">{{ $n }}</span>
                        <h3 class="mt-3 font-bold">{!! $title !!}</h3>
                        <p class="mt-1 text-sm text-slate-600">{!! $desc !!}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- What you provide --}}
    <section class="container-px py-16">
        <div class="mx-auto max-w-3xl">
            <h2 class="text-3xl font-bold">What we'll need from you</h2>
            @php
                $needs = $package && $package->intake_questions
                    ? collect($package->intake_questions)->pluck('label')->filter()->values()->all()
                    : ['Your business name and a short description', 'Your logo and any brand colours', 'The pages you need (e.g. Home, About, Services, Contact)', 'Any text, images or content you already have', 'Example websites you like'];
            @endphp
            <ul class="mt-6 space-y-3 text-slate-700">
                @foreach ($needs as $item)
                    <li class="flex items-start gap-3">
                        <svg class="mt-1 h-5 w-5 flex-shrink-0 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- FAQ --}}
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
                <h2 class="text-center text-3xl font-bold sm:text-4xl">Common questions</h2>
                <div class="mt-10 space-y-3" x-data="{ open: null }">
                    @foreach ($faqs as $i => $faq)
                        <div class="card-dash">
                            <h3>
                                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                        class="flex w-full items-center justify-between gap-4 text-left text-base font-semibold text-slate-900"
                                        :aria-expanded="open === {{ $i }}" aria-controls="wfaq-{{ $i }}">
                                    {{ $faq->question }}
                                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </h3>
                            <div id="wfaq-{{ $i }}" x-show="open === {{ $i }}" x-collapse x-cloak class="mt-3 text-sm text-slate-600">{{ $faq->answer }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== Closing CTA ===================== --}}
    {{-- The renewal honesty note and the buy button used to sit apart as two
         bare blocks. They belong together: the last thing a customer reads
         before paying should be exactly what they pay now and what renews
         later, in one place. --}}
    <section class="relative overflow-hidden hero-aurora text-white">
        <div class="absolute inset-0 hero-grid opacity-60" aria-hidden="true"></div>

        <div class="container-px relative section">
            <div class="mx-auto grid max-w-5xl items-center gap-10 lg:grid-cols-2 lg:gap-14">
                {{-- Pitch --}}
                <div>
                    <p class="eyebrow">Ready when you are</p>
                    <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">
                        Your complete website for <span class="gradient-text">{{ money_compact($price) }}</span>
                    </h2>
                    <p class="mt-4 max-w-lg text-lg text-slate-300">
                        One payment covers the design, the build and the launch — plus your domain and hosting for the
                        first year. No monthly fee to start, and nothing technical for you to set up.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ region()->route('cart.items.store') }}">
                            @csrf
                            <input type="hidden" name="item_type" value="website_package">
                            @if ($product)<input type="hidden" name="product_id" value="{{ $product->id }}">@endif
                            <button type="submit" class="btn-primary">Get your website for {{ money_compact($price) }}</button>
                        </form>
                        <a href="{{ region()->route('contact') }}" class="btn-ghost-dark">Talk to us first</a>
                    </div>

                    <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-300">
                        @foreach ([
                            ['Secure Stripe checkout', 'M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM7 11V7a5 5 0 0 1 10 0v4'],
                            ['Live in about two weeks', 'M12 7v5l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z'],
                            ['Real people, UK support', 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
                        ] as [$label, $icon])
                            <li class="flex items-center gap-2">
                                <svg class="h-4 w-4 flex-shrink-0 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                                {{ $label }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- What you actually pay --}}
                <div class="rounded-[22px] border border-white/12 bg-white/[0.06] p-6 shadow-dark backdrop-blur sm:p-8">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-300">What you pay today</h3>

                    <dl class="mt-5 space-y-3 text-sm">
                        @foreach ([
                            ['Bespoke website, built for you', money_compact($price)],
                            ['Domain name — first year', 'Free'],
                            ['Hosting, SSL &amp; email setup — first year', 'Free'],
                        ] as [$item, $amount])
                            <div class="flex items-baseline justify-between gap-4 border-b border-white/10 pb-3">
                                <dt class="text-slate-300">{!! $item !!}</dt>
                                <dd class="whitespace-nowrap font-semibold text-white">{{ $amount }}</dd>
                            </div>
                        @endforeach
                        <div class="flex items-baseline justify-between gap-4 pt-1">
                            <dt class="font-semibold text-white">Total today</dt>
                            <dd class="text-3xl font-extrabold text-white">{{ money_compact($price) }}</dd>
                        </div>
                    </dl>

                    {{-- Renewal honesty. Deliberately inside the price summary
                         rather than in a separate box further up the page: it is
                         part of the price, so it is read as part of the price. --}}
                    <div class="mt-6 rounded-[14px] border border-accent-cyan/25 bg-accent-cyan/10 p-4">
                        <p class="flex items-center gap-2 text-sm font-bold text-white">
                            <svg class="h-4 w-4 flex-shrink-0 text-accent-cyan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                            After the first year
                        </p>
                        <p class="mt-1.5 text-sm text-slate-200">
                            Your domain and hosting renew at our standard rates — we remind you well before anything is
                            charged. Full details in the
                            <a href="{{ region()->route('legal.renewal') }}" class="font-semibold text-white underline decoration-accent-cyan underline-offset-2">Renewal Policy</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
