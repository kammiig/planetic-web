@php
    use App\Support\Region;

    $currentRegion = $region ?? Region::current();
    $currentPath = '/'.ltrim(request()->path(), '/');
    $regions = Region::all();
@endphp

{{--
    Manual storefront selector.

    These are ordinary links to the other storefront's URL for the SAME page —
    not a form that reloads this URL with different content. That matters: the
    region lives in the URL, so switching genuinely navigates, each version stays
    independently cacheable, and crawlers can follow the links to discover and
    index both trees (reinforcing the hreflang tags in the layout).

    A small cookie is written on click purely so the geolocation banner stops
    suggesting a region the visitor has already decided against.
--}}
@if (count($regions) > 1)
    <div x-data="{ open: false }" @keydown.escape="open = false" class="relative">
        <button type="button" @click="open = !open" :aria-expanded="open"
                class="inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm text-inherit transition hover:opacity-80"
                aria-haspopup="true" aria-label="Change region and currency">
            <span aria-hidden="true">{{ $currentRegion->flag() }}</span>
            <span class="font-medium">{{ $currentRegion->currency() }}</span>
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-slate-700 shadow-lg">
            @foreach ($regions as $r)
                <a href="{{ url($r->translatePath($currentPath)) }}"
                   @click="document.cookie = '{{ config('regions.cookie') }}={{ $r->key }}; path=/; max-age={{ (int) config('regions.cookie_days', 365) * 86400 }}; SameSite=Lax'"
                   hreflang="{{ $r->hreflang() }}"
                   @class([
                       'flex items-center justify-between gap-3 px-3 py-2 text-sm hover:bg-slate-50',
                       'font-semibold text-primary-600' => $r->key === $currentRegion->key,
                   ])
                   @if ($r->key === $currentRegion->key) aria-current="true" @endif>
                    <span><span aria-hidden="true">{{ $r->flag() }}</span> {{ $r->name() }}</span>
                    <span class="text-slate-400">{{ $r->symbol() }} {{ $r->currency() }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
