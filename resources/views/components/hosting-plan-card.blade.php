@props([
    'product',
    'recommended' => false,
])

@php
    $pkg = $product->hostingPackage;
    $monthly = $product->priceFor('monthly');
    $yearly = $product->priceFor('yearly');
    $isPopular = $recommended || (bool) ($pkg?->is_popular);
    $features = $pkg ? $pkg->featureList() : [];
@endphp

<div class="card lift flex flex-col {{ $isPopular ? 'card-popular' : '' }}">
    @if ($isPopular)
        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-500 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white shadow-soft">
            Most popular
        </span>
    @endif

    <h3 class="text-xl font-bold text-slate-900">{{ $product->name }}</h3>
    <p class="mt-1 text-sm text-slate-500">{{ $pkg?->tagline ?: $product->description }}</p>

    <div class="mt-5 border-b border-slate-100 pb-5">
        @if ($monthly)
            <p x-show="cycle === 'monthly'">
                <span class="text-4xl font-extrabold text-slate-900">£{{ number_format($monthly->amount, 2) }}</span>
                <span class="text-slate-500">/month</span>
            </p>
        @endif
        @if ($yearly)
            <p x-show="cycle === 'yearly'" x-cloak>
                <span class="text-4xl font-extrabold text-slate-900">£{{ number_format($yearly->amount, 2) }}</span>
                <span class="text-slate-500">/year</span>
            </p>
        @endif
    </div>

    <ul class="mt-5 flex-1 space-y-2.5 text-sm text-slate-600">
        @foreach ($features as $feature)
            <li class="flex items-start gap-2.5">
                <span class="feature-check" aria-hidden="true">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <span>{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6">
        @csrf
        <input type="hidden" name="item_type" value="hosting">
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="billing_cycle" :value="cycle" value="monthly">
        <button type="submit" class="{{ $isPopular ? 'btn-primary' : 'btn-secondary' }} w-full">
            Choose {{ $product->name }}
        </button>
    </form>
</div>
