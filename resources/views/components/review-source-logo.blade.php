@props([
    'source', // App\Enums\ReviewSource
])

{{-- Brand marks are only ever rendered for the source the admin selected.
     Manual reviews show a neutral verified-customer chip, never a third-party logo. --}}
@switch($source->value)
    @case('trustpilot')
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }} aria-label="Trustpilot">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#00b67a" d="M12 1.6l2.74 6.9 7.41.32-5.78 4.65 1.96 7.15L12 16.9l-6.29 3.72 1.96-7.15-5.78-4.65 7.41-.32z"/>
            </svg>
            <span class="text-sm font-bold tracking-tight text-slate-900">Trustpilot</span>
        </span>
        @break

    @case('google')
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }} aria-label="Google">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/>
                <path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/>
                <path fill="#FBBC05" d="M11.69 28.18c-.44-1.32-.69-2.73-.69-4.18s.25-2.86.69-4.18v-5.7H4.34A21.99 21.99 0 0 0 2 24c0 3.55.85 6.91 2.34 9.88l7.35-5.7z"/>
                <path fill="#EA4335" d="M24 9.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 3.18 29.93 1 24 1 15.4 1 7.96 5.93 4.34 13.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/>
            </svg>
            <span class="text-sm font-bold tracking-tight text-slate-900">Google</span>
        </span>
        @break

    @default
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-primary-600']) }} aria-label="Verified customer">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 2 4 6v6c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6z"/><path d="m9 12 2 2 4-4"/>
            </svg>
            <span class="text-sm font-bold tracking-tight text-slate-900">Verified customer</span>
        </span>
@endswitch
