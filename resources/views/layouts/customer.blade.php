@php
    $dashNav = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'],
        ['label' => 'Domains', 'route' => 'customer.domains.index', 'icon' => 'M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z'],
        ['label' => 'Hosting', 'route' => 'customer.hosting.index', 'icon' => 'M3 5h18v6H3zM3 13h18v6H3zM7 8h.01M7 16h.01'],
        ['label' => 'Billing', 'route' => 'customer.billing.index', 'icon' => 'M3 6h18v12H3zM3 10h18'],
        ['label' => 'Website Projects', 'route' => 'customer.projects.index', 'icon' => 'M4 4h16v12H4zM2 20h20M9 8h6M9 12h6'],
        ['label' => 'Support', 'route' => 'customer.support.index', 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
        ['label' => 'Account Settings', 'route' => 'customer.settings.edit', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 2h-5l-.3 2.6a7 7 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.6h5l.3-2.6a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6c.07-.33.1-.66.1-1z'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-full bg-slate-50" x-data="{ sidebar: false }">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside x-cloak
               class="fixed inset-y-0 left-0 z-50 w-[280px] -translate-x-full transform bg-primary-950 text-slate-300 transition-transform lg:static lg:translate-x-0"
               :class="sidebar && '!translate-x-0'"
               aria-label="Dashboard navigation">
            <div class="flex h-[72px] items-center justify-between px-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-white">
                    <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-primary-500" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/></svg>
                    </span>
                    <span class="font-extrabold">Planetic Web</span>
                </a>
                <button type="button" @click="sidebar = false" class="grid h-10 w-10 place-items-center rounded-md text-slate-400 hover:text-white lg:hidden" aria-label="Close menu">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

            <nav class="px-3 py-2">
                <ul class="space-y-1">
                    @foreach ($dashNav as $item)
                        @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}"
                               @if ($active) aria-current="page" @endif
                               class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-primary-500 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-3 border-t border-white/10 pt-3">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener"
                       class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                        <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/></svg>
                        Visit website
                    </a>
                </div>
            </nav>

            <div class="absolute inset-x-0 bottom-0 border-t border-white/10 p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        {{-- Overlay for mobile --}}
        <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-40 bg-primary-950/70 lg:hidden" aria-hidden="true"></div>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex h-[72px] items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                <button type="button" @click="sidebar = true" class="grid h-11 w-11 place-items-center rounded-md text-slate-700 lg:hidden" aria-label="Open menu" aria-controls="main-content">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-lg font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                <div class="flex items-center gap-3">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener"
                       class="hidden items-center gap-1.5 rounded-[10px] border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-primary-300 hover:text-primary-600 sm:inline-flex">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3"/></svg>
                        Visit website
                    </a>
                    <span class="hidden text-sm text-slate-500 sm:inline">{{ auth()->user()?->name }}</span>
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-primary-100 text-sm font-bold text-primary-600" aria-hidden="true">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </span>
                </div>
            </header>

            <main id="main-content" class="flex-1">
                <div class="container-dash py-6 sm:py-8">
                    @if (auth()->check() && ! auth()->user()->hasVerifiedEmail())
                        <div class="alert alert-warning mb-4 flex flex-wrap items-center justify-between gap-3" role="status">
                            <span>
                                <strong class="font-semibold">Please verify your email address.</strong>
                                We sent a link to {{ auth()->user()->email }} — verifying keeps your account secure. Your services are not affected.
                            </span>
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="whitespace-nowrap rounded-[10px] border border-warning/40 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:border-warning">
                                    Resend verification email
                                </button>
                            </form>
                        </div>
                    @endif
                    @include('partials.flash')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
