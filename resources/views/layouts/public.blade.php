@php
    use App\Models\SeoMeta;

    $defaultTitle = 'Premium Hosting, Domains & Bespoke Websites';
    $defaultDescription = 'Planetic Web builds your complete bespoke website, registers your domain, sets up hosting and DNS, and manages billing and renewals — all in one place.';

    // Admin-managed SEO for this route wins; otherwise fall back to the page's
    // own @section, then the site defaults. Decode entities first so a value
    // containing e.g. &amp; is not double-encoded when re-escaped below.
    $seo = SeoMeta::forKey(request()->route()?->getName());

    $rawTitle = $seo?->meta_title ?: $__env->yieldContent('title', $defaultTitle);
    $rawDescription = $seo?->meta_description ?: $__env->yieldContent('meta_description', $defaultDescription);

    $metaTitle = html_entity_decode($rawTitle, ENT_QUOTES, 'UTF-8').' · '.config('app.name');
    $metaDescription = html_entity_decode($rawDescription, ENT_QUOTES, 'UTF-8');
    $canonicalUrl = $seo?->canonical_url ?: url()->current();

    $ogTitle = $seo?->og_title ? html_entity_decode($seo->og_title, ENT_QUOTES, 'UTF-8') : $metaTitle;
    $ogDescription = $seo?->og_description ? html_entity_decode($seo->og_description, ENT_QUOTES, 'UTF-8') : $metaDescription;
    $ogImage = $seo?->og_image;
    $twitterCard = $seo?->twitter_card ?: 'summary_large_image';
    $twitterTitle = $seo?->twitter_title ? html_entity_decode($seo->twitter_title, ENT_QUOTES, 'UTF-8') : $ogTitle;
    $twitterDescription = $seo?->twitter_description ? html_entity_decode($seo->twitter_description, ENT_QUOTES, 'UTF-8') : $ogDescription;

    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => setting('company.name', config('app.name')),
        'url' => config('app.url'),
        'logo' => asset('favicon.ico'),
        'description' => $defaultDescription,
        'email' => setting('contact.email', 'support@planeticweb.com'),
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'email' => setting('contact.email', 'support@planeticweb.com'),
            'telephone' => setting('contact.phone', ''),
            'contactType' => 'customer support',
            'areaServed' => 'GB',
            'availableLanguage' => 'English',
        ],
        'sameAs' => array_values(array_filter([
            setting('social.facebook'), setting('social.twitter'),
            setting('social.instagram'), setting('social.linkedin'),
        ])),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($seo?->noindex)
        <meta name="robots" content="noindex, follow">
    @endif
    <meta name="theme-color" content="#0b1b33">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="en_GB">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Organisation schema (site-wide) --}}
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    {{-- Optional per-page JSON-LD from the admin SEO settings --}}
    @if (filled($seo?->schema_json))
        <script type="application/ld+json">{!! $seo->schema_json !!}</script>
    @endif

    @stack('head')
</head>
<body class="flex min-h-full flex-col bg-white">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @include('partials.public-header')

    <main id="main-content" class="flex-1">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.public-footer')

    @stack('scripts')
</body>
</html>
