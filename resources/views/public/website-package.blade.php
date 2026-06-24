@extends('layouts.public')

@section('title', 'Complete Bespoke Website for £200')
@section('meta_description', 'A complete bespoke website for £200, with free domain and hosting for the first year. Renewal applies after the first year.')

@php
    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'Complete Bespoke Website',
        'description' => 'A complete bespoke business website with a free domain and hosting for the first year, basic SEO and Cloudflare setup, built and managed by Planetic Web.',
        'brand' => ['@type' => 'Brand', 'name' => config('app.name')],
        'offers' => [
            '@type' => 'Offer',
            'price' => number_format((float) ($price ?? 200), 2, '.', ''),
            'priceCurrency' => 'GBP',
            'availability' => 'https://schema.org/InStock',
            'url' => route('website-package'),
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
                Complete Bespoke Website for £{{ number_format($price, 0) }}
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg font-medium text-accent-cyan">
                {{ $freeYearNotice }}
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <form method="POST" action="{{ route('cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="item_type" value="website_package">
                    @if ($product)<input type="hidden" name="product_id" value="{{ $product->id }}">@endif
                    <button type="submit" class="btn-primary">Start Now</button>
                </form>
                <a href="{{ route('domains.index') }}" class="btn-ghost-dark">Search a domain first</a>
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

    {{-- Renewal notice --}}
    <section class="container-px pb-16">
        <div class="alert alert-info mx-auto max-w-3xl">
            <h2 class="text-lg font-bold">About renewals</h2>
            <p class="mt-2">
                The £{{ number_format($price, 0) }} website package includes your domain and hosting free for the
                <strong>first year only</strong>. After the first year, standard domain and hosting renewal charges apply.
                See our <a href="{{ route('legal.renewal') }}" class="font-semibold underline">Renewal Policy</a> for full details.
            </p>
        </div>
    </section>

    {{-- CTA --}}
    <section class="container-px pb-20 text-center">
        <form method="POST" action="{{ route('cart.items.store') }}" class="inline">
            @csrf
            <input type="hidden" name="item_type" value="website_package">
            @if ($product)<input type="hidden" name="product_id" value="{{ $product->id }}">@endif
            <button type="submit" class="btn-primary">Get your website for £{{ number_format($price, 0) }}</button>
        </form>
    </section>
@endsection
