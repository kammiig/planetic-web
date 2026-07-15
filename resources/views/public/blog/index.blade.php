@extends('layouts.public')

@section('title', 'Web Design Advice for UK Small Businesses — Blog')
@section('meta_description', 'Honest, practical advice on websites, domains and hosting for UK small businesses — costs, comparisons and what actually matters, from the Planetic Web team.')

@section('content')
    <section class="relative overflow-hidden hero-gradient text-white">
        <div class="absolute inset-0 hero-grid opacity-70" aria-hidden="true"></div>
        <div class="container-px relative py-14 text-center lg:py-20">
            <p class="eyebrow">Blog</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">Plain-English website advice</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-300">Costs, comparisons and what actually matters when getting your business online — with real numbers, not sales talk.</p>
        </div>
    </section>

    <section class="container-px section">
        @if ($posts->isEmpty())
            <p class="text-center text-slate-500">No articles published yet — check back soon.</p>
        @else
            <div class="mx-auto grid max-w-6xl gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ route('blog.show', $post->slug) }}" class="offer-card group">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
                            · {{ $post->readingMinutes() }} min read
                        </p>
                        <h2 class="mt-3 text-xl font-bold text-slate-900 group-hover:text-primary-600">{{ $post->title }}</h2>
                        <p class="mt-3 flex-1 text-sm leading-relaxed text-slate-600">{{ $post->excerptText() }}</p>
                        <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-primary-600">Read article
                            <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>

            @if ($posts->hasPages())
                <nav class="mx-auto mt-12 flex max-w-6xl items-center justify-between" aria-label="Blog pagination">
                    @if ($posts->onFirstPage())
                        <span></span>
                    @else
                        <a href="{{ $posts->previousPageUrl() }}" class="btn-secondary">&larr; Newer articles</a>
                    @endif
                    @if ($posts->hasMorePages())
                        <a href="{{ $posts->nextPageUrl() }}" class="btn-secondary">Older articles &rarr;</a>
                    @endif
                </nav>
            @endif
        @endif
    </section>

    {{-- CTA --}}
    <section class="bg-slate-50">
        <div class="container-px section text-center">
            <h2 class="text-2xl font-bold sm:text-3xl">Need a website without the hassle?</h2>
            <p class="mx-auto mt-3 max-w-xl text-slate-600">A complete bespoke website for £200, with a free domain and hosting for the first year.</p>
            <a href="{{ route('website-package') }}" class="btn-primary mt-6 inline-flex">See the £200 website package</a>
        </div>
    </section>
@endsection
