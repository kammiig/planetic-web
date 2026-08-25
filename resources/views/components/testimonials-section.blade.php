@props([
    // App\Models\Testimonial collection — the SAME admin-managed rows on every
    // page that shows reviews, so editing a testimonial once updates them all.
    'testimonials',
])

@if ($testimonials->isNotEmpty())
    @php
        $reviewCount = $testimonials->count();
        $avgRating = round($testimonials->avg('rating'), 1);
        $avgStars = (int) round($avgRating);
    @endphp

    <section {{ $attributes->merge(['class' => 'section']) }}>
        <div class="container-px">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">{{ setting('testimonials.eyebrow', 'Verified customer reviews') }}</p>
                <h2 class="mt-3 text-3xl font-bold sm:text-4xl">{{ setting('testimonials.title', 'Trusted by businesses across the UK') }}</h2>
                <div class="mt-5 flex justify-center">
                    <span class="rating-summary">
                        <span class="review-stars" aria-hidden="true">{!! str_repeat('★', $avgStars).str_repeat('☆', 5 - $avgStars) !!}</span>
                        <span class="text-sm font-bold text-slate-900">{{ number_format($avgRating, 1) }} out of 5</span>
                        <span class="text-sm text-slate-500">· based on {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</span>
                    </span>
                </div>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonials as $t)
                    <x-review-card :testimonial="$t" />
                @endforeach
            </div>
        </div>
    </section>
@endif
