<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Premium Hosting, Domains & Bespoke Websites') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Planetic Web builds your complete bespoke website, registers your domain, sets up hosting and DNS, and manages billing and renewals — all in one place.')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
