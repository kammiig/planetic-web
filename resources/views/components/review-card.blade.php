@props([
    'testimonial',
])

@php
    $t = $testimonial;
    $source = $t->source();
    $rating = max(0, min(5, (int) $t->rating));
    // Only branded sources link out; never link a manual review to a third party.
    $href = ($source->isBranded() && filled($t->source_url)) ? $t->source_url : null;
@endphp

<figure class="review-card flex h-full flex-col">
    {{-- Top row: source logo + verified label --}}
    <div class="flex items-center justify-between gap-3">
        <x-review-source-logo :source="$source" />
        <span class="badge {{ $source->badgeClass() }}">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            {{ $t->sourceBadgeLabel() }}
        </span>
    </div>

    {{-- Stars --}}
    <div class="mt-4 flex items-center gap-2">
        <span class="review-stars" role="img" aria-label="{{ $rating }} out of 5 stars">
            {!! str_repeat('★', $rating).str_repeat('☆', 5 - $rating) !!}
        </span>
    </div>

    {{-- Quote --}}
    <blockquote class="mt-3 flex-1 text-slate-700">“{{ $t->body }}”</blockquote>

    {{-- Reviewer --}}
    <figcaption class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
        @if ($t->avatar_url)
            <img src="{{ $t->avatar_url }}" alt="{{ $t->author_name }}" class="h-11 w-11 rounded-full object-cover" loading="lazy">
        @else
            <span class="grid h-11 w-11 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-700" aria-hidden="true">{{ $t->initials() }}</span>
        @endif
        <span class="min-w-0">
            <span class="block truncate text-sm font-bold text-slate-900">{{ $t->author_name }}</span>
            <span class="block truncate text-xs text-slate-500">{{ trim(($t->author_role ? $t->author_role.', ' : '').$t->company, ', ') }}</span>
        </span>
        @if ($href)
            <a href="{{ $href }}" target="_blank" rel="noopener nofollow" class="ml-auto text-xs font-semibold text-primary-600 hover:underline">
                Read review<span class="sr-only"> on {{ $source->label() }} (opens in a new tab)</span>
            </a>
        @endif
    </figcaption>
</figure>
