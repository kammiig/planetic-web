@php
    $defaultTitle = 'Premium Hosting, Domains & Bespoke Websites';
    $defaultDescription = 'Planetic Web builds your complete bespoke website, registers your domain, sets up hosting and DNS, and manages billing and renewals — all in one place.';
    // Decode first so a section value containing an entity (e.g. &amp;) is not
    // double-encoded when Blade re-escapes it in the tags below.
    $metaTitle = html_entity_decode($__env->yieldContent('title', $defaultTitle), ENT_QUOTES, 'UTF-8').' · '.config('app.name');
    $metaDescription = html_entity_decode($__env->yieldContent('meta_description', $defaultDescription), ENT_QUOTES, 'UTF-8');
    $canonicalUrl = url()->current();
    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => config('app.url'),
        'logo' => asset('favicon.ico'),
        'description' => $defaultDescription,
        'email' => 'support@planeticweb.com',
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'email' => 'support@planeticweb.com',
            'contactType' => 'customer support',
            'areaServed' => 'GB',
            'availableLanguage' => 'English',
        ],
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
    <meta name="theme-color" content="#0b1b33">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="en_GB">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Organisation schema (site-wide) --}}
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

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
