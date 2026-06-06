<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'Planetic Web') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full items-center justify-center bg-slate-50 px-4">
    <main class="w-full max-w-lg text-center">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2" aria-label="{{ config('app.name') }} home">
            <span class="grid h-10 w-10 place-items-center rounded-[10px] bg-primary-500 text-white" aria-hidden="true">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/></svg>
            </span>
            <span class="text-xl font-extrabold text-slate-900">Planetic<span class="text-primary-500">Web</span></span>
        </a>

        <p class="mt-10 text-6xl font-extrabold text-primary-500">@yield('code')</p>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">@yield('title')</h1>
        <p class="mt-3 text-slate-600">@yield('message')</p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="btn-primary">Back to home</a>
            <a href="{{ url('/contact') }}" class="btn-secondary">Contact support</a>
        </div>
    </main>
</body>
</html>
