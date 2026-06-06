@php
    $navItems = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Domains', 'route' => 'domains.index'],
        ['label' => 'Hosting', 'route' => 'hosting.index'],
        ['label' => 'Website Package', 'route' => 'website-package'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white">
    <nav class="container-px flex h-[64px] items-center justify-between md:h-[72px]" aria-label="Primary">
        <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="{{ config('app.name') }} home">
            <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-primary-500 text-white" aria-hidden="true">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18" />
                </svg>
            </span>
            <span class="text-lg font-extrabold tracking-tight text-slate-900">Planetic<span class="text-primary-500">Web</span></span>
        </a>

        <ul class="hidden items-center gap-1 md:flex">
            @foreach ($navItems as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       class="nav-link {{ request()->routeIs($item['route']) ? 'nav-link-active' : '' }}"
                       @if (request()->routeIs($item['route'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="hidden items-center gap-3 md:flex">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-secondary btn-sm">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="nav-link">Client Login</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm">Get Started</a>
            @endauth
            <a href="{{ route('cart.index') }}" class="nav-link" aria-label="View cart">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M2 3h3l2.4 12.6a1 1 0 0 0 1 .8h9.5a1 1 0 0 0 1-.8L21 7H6"/></svg>
            </a>
        </div>

        <button type="button" @click="open = !open"
                class="grid h-11 w-11 place-items-center rounded-md text-slate-700 md:hidden"
                :aria-expanded="open" aria-controls="mobile-menu" aria-label="Toggle navigation menu">
            <svg x-show="!open" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="open" x-cloak class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </nav>

    <div id="mobile-menu" x-show="open" x-cloak @keydown.escape.window="open = false"
         class="border-t border-slate-200 bg-white md:hidden">
        <ul class="container-px flex flex-col gap-1 py-3">
            @foreach ($navItems as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       class="block rounded-md px-3 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 {{ request()->routeIs($item['route']) ? 'nav-link-active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
            <li class="mt-2 flex flex-col gap-2 border-t border-slate-200 pt-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-secondary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary">Client Login</a>
                    <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                @endauth
            </li>
        </ul>
    </div>
</header>
