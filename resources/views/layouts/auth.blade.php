<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="container-px flex h-[64px] items-center md:h-[72px]">
                <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="{{ config('app.name') }} home">
                    <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-primary-500 text-white" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/></svg>
                    </span>
                    <span class="text-lg font-extrabold tracking-tight">Planetic<span class="text-primary-500">Web</span></span>
                </a>
            </div>
        </header>

        <main id="main-content" class="flex flex-1 items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                @include('partials.flash')
                <div class="card">
                    @yield('content')
                </div>
                <p class="mt-6 text-center text-sm text-slate-500">
                    Secure client portal · <a class="font-medium text-primary-600 hover:underline" href="{{ route('home') }}">Back to website</a>
                </p>
            </div>
        </main>
    </div>
</body>
</html>
