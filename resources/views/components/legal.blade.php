@props([
    'title',
    'updated' => null,
])

<section class="container-px py-16">
    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-bold text-slate-900">{{ $title }}</h1>
        @if ($updated)
            <p class="mt-2 text-sm text-slate-500">Last updated {{ $updated }}</p>
        @endif
        <div class="mt-8 space-y-5 text-slate-700 [&_h2]:mt-8 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-slate-900 [&_p]:leading-relaxed [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-6 [&_a]:font-semibold [&_a]:text-primary-600 [&_a:hover]:underline">
            {{ $slot }}
        </div>
    </div>
</section>
