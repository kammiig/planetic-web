@extends('layouts.public')

@section('title', 'UK Web Hosting — Fast cPanel Hosting with Free SSL')
@section('meta_description', 'Fast, secure UK web hosting with cPanel, free SSL, Cloudflare DNS and business email. Monthly or yearly plans for small businesses — upgrade any time.')

@section('content')
    <section class="relative overflow-hidden hero-gradient text-white">
        <div class="absolute inset-0 hero-grid opacity-70" aria-hidden="true"></div>
        <div class="container-px relative py-14 text-center lg:py-20">
            <p class="eyebrow">Web Hosting</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Hosting that just works</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-300">Free SSL, cPanel and automatic Cloudflare DNS on every plan. Upgrade any time.</p>
        </div>
    </section>

    <section class="container-px section" x-data="{ cycle: 'monthly' }">
        <div class="flex justify-center">
            <div class="inline-flex rounded-full border border-slate-200 bg-white p-1" role="group" aria-label="Billing cycle">
                <button type="button" @click="cycle = 'monthly'" :class="cycle === 'monthly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-5 py-2 text-sm font-semibold transition">Monthly</button>
                <button type="button" @click="cycle = 'yearly'" :class="cycle === 'yearly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-5 py-2 text-sm font-semibold transition">Yearly</button>
            </div>
        </div>

        @if ($plans->isEmpty())
            <p class="mt-10 text-center text-slate-500">Hosting plans are being set up. Please check back shortly.</p>
        @else
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($plans as $plan)
                    <x-hosting-plan-card :product="$plan" />
                @endforeach
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-slate-500">
            Need a website too? The <a href="{{ route('website-package') }}" class="font-semibold text-primary-600 hover:underline">£200 website package</a> includes free hosting for the first year.
        </p>
    </section>

    <section class="bg-slate-50 section">
        <div class="container-px grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Free SSL', 'Every site is secured with a free, auto-renewing SSL certificate.', 'M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM7 11V7a5 5 0 0 1 10 0v4'],
                ['Cloudflare DNS', 'We configure Cloudflare DNS, proxy and Always-Use-HTTPS for you.', 'M12 2 4 6v6c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6z'],
                ['cPanel included', 'Manage email, files and databases with the familiar cPanel interface.', 'M2 4h20v12H2zM2 9h20'],
            ] as [$t, $d, $icon])
                <div class="card lift">
                    <span class="grid h-12 w-12 place-items-center rounded-[14px] bg-primary-50 text-primary-600" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $icon }}"/></svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold">{{ $t }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>

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
                <h2 class="text-center text-3xl font-bold sm:text-4xl">Hosting questions, answered</h2>
                <div class="mt-10 space-y-3" x-data="{ open: null }">
                    @foreach ($faqs as $i => $faq)
                        <div class="card-dash">
                            <h3>
                                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                        class="flex w-full items-center justify-between gap-4 text-left text-base font-semibold text-slate-900"
                                        :aria-expanded="open === {{ $i }}" aria-controls="hfaq-{{ $i }}">
                                    {{ $faq->question }}
                                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400 transition" :class="open === {{ $i }} && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </h3>
                            <div id="hfaq-{{ $i }}" x-show="open === {{ $i }}" x-collapse x-cloak class="mt-3 text-sm text-slate-600">{{ $faq->answer }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
