@php
    use App\Support\Region;

    $currentRegion = $region ?? Region::current();
    $currentPath = '/'.ltrim(request()->path(), '/');

    // A static map of every storefront, identical in every response. The page
    // itself never knows the visitor's country — the client asks /region-hint
    // for that — so this HTML stays byte-identical for all visitors and stays
    // safely cacheable at the CDN.
    $regionLinks = collect(Region::all())->map(fn (Region $r) => [
        'key' => $r->key,
        'label' => $r->name(),
        'short' => $r->shortName(),
        'flag' => $r->flag(),
        'currency' => $r->currency(),
        'url' => url($r->translatePath($currentPath)),
    ])->values()->all();
@endphp

{{--
    Geolocated region suggestion.

    Deliberately client-side. Rendering this server-side from CF-IPCountry would
    make the page vary by visitor, which (a) Cloudflare will not key its cache on
    outside Enterprise plans, so the first visitor's country would be cached for
    everyone, and (b) hides one storefront from Googlebot, which crawls mostly
    from US IPs. Fetching the hint separately keeps the page identical for all.

    It never redirects: it offers, and the visitor chooses.
--}}
<div
    x-data="regionSuggestion({
        current: @js($currentRegion->key),
        hintUrl: @js(route('region.hint')),
        cookie: @js(config('regions.cookie')),
        cookieDays: @js((int) config('regions.cookie_days', 365)),
        regions: @js($regionLinks),
    })"
    x-init="init()"
    x-cloak
>
    <template x-if="show">
        <div class="border-b border-primary-200 bg-primary-50" role="region" aria-label="Regional storefront suggestion">
            <div class="container-px flex flex-wrap items-center justify-center gap-x-3 gap-y-2 py-2.5 text-sm text-slate-700">
                <span>
                    <span x-text="suggestion.flag"></span>
                    Looks like you're in <span class="font-semibold" x-text="suggestion.label"></span>.
                    View prices in <span class="font-semibold" x-text="suggestion.currency"></span>?
                </span>
                <a :href="suggestion.url" @click="remember(suggestion.key)"
                   class="btn-primary btn-sm">Switch to <span x-text="suggestion.currency"></span></a>
                <button type="button" @click="dismiss()"
                        class="text-slate-500 underline underline-offset-2 hover:text-slate-700">
                    Stay on <span x-text="currentLabel"></span>
                </button>
            </div>
        </div>
    </template>
</div>
