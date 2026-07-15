@extends('layouts.public')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description ?: $post->excerptText())

@php
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $post->meta_description ?: $post->excerptText(),
        'image' => asset('images/og-default.png'),
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
        'mainEntityOfPage' => route('blog.show', $post->slug),
    ];

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => route('blog.index')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title],
        ],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article>
        <header class="relative overflow-hidden hero-gradient text-white">
            <div class="absolute inset-0 hero-grid opacity-70" aria-hidden="true"></div>
            <div class="container-px relative py-14 lg:py-16">
                <div class="mx-auto max-w-3xl">
                    <nav class="text-xs font-semibold uppercase tracking-wide text-slate-400" aria-label="Breadcrumb">
                        <a href="{{ route('home') }}" class="hover:text-white">Home</a>
                        <span aria-hidden="true"> / </span>
                        <a href="{{ route('blog.index') }}" class="hover:text-white">Blog</a>
                    </nav>
                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $post->title }}</h1>
                    <p class="mt-4 text-sm text-slate-300">
                        By {{ config('app.name') }} ·
                        <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j F Y') }}</time>
                        · {{ $post->readingMinutes() }} min read
                    </p>
                </div>
            </div>
        </header>

        <div class="container-px py-12 lg:py-16">
            <div class="prose-blog mx-auto max-w-3xl">
                {!! $post->bodyHtml() !!}
            </div>
        </div>
    </article>

    {{-- CTA --}}
    <section class="bg-slate-50">
        <div class="container-px section text-center">
            <h2 class="text-2xl font-bold sm:text-3xl">Ready for a website that just gets done?</h2>
            <p class="mx-auto mt-3 max-w-xl text-slate-600">A complete bespoke website for £200 — free domain and hosting for the first year, SSL and email set up for you.</p>
            <a href="{{ route('website-package') }}" class="btn-primary mt-6 inline-flex">See the £200 website package</a>
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="container-px section">
            <h2 class="text-center text-2xl font-bold">More from the blog</h2>
            <div class="mx-auto mt-10 grid max-w-6xl gap-6 md:grid-cols-3">
                @foreach ($related as $r)
                    <a href="{{ route('blog.show', $r->slug) }}" class="offer-card group">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $r->published_at?->format('j M Y') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-primary-600">{{ $r->title }}</h3>
                        <p class="mt-2 flex-1 text-sm text-slate-600">{{ $r->excerptText() }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
