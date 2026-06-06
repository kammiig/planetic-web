@props([
    'product',
    'recommended' => false,
])

@php
    $pkg = $product->hostingPackage;
    $monthly = $product->priceFor('monthly');
    $yearly = $product->priceFor('yearly');
    $features = array_filter([
        $pkg?->diskLabel() ? $pkg->diskLabel().' SSD storage' : null,
        $pkg && $pkg->bandwidth_limit_mb ? round($pkg->bandwidth_limit_mb / 1024).' GB bandwidth' : 'Unmetered bandwidth',
        $pkg && $pkg->email_accounts_limit ? $pkg->email_accounts_limit.' email accounts' : 'Unlimited email accounts',
        'Free SSL certificate',
        'cPanel included',
        'Cloudflare DNS setup',
    ]);
@endphp

<div class="card flex flex-col {{ $recommended ? 'border-2 border-primary-500 shadow-large' : '' }}">
    @if ($recommended)
        <span class="badge badge-primary mb-3 self-start">Recommended</span>
    @endif

    <h3 class="text-xl font-bold text-slate-900">{{ $product->name }}</h3>
    <p class="mt-1 text-sm text-slate-500">{{ $product->description }}</p>

    <div class="mt-5">
        @if ($monthly)
            <p x-show="cycle === 'monthly'">
                <span class="text-3xl font-extrabold text-slate-900">£{{ number_format($monthly->amount, 2) }}</span>
                <span class="text-slate-500">/month</span>
            </p>
        @endif
        @if ($yearly)
            <p x-show="cycle === 'yearly'" x-cloak>
                <span class="text-3xl font-extrabold text-slate-900">£{{ number_format($yearly->amount, 2) }}</span>
                <span class="text-slate-500">/year</span>
            </p>
        @endif
    </div>

    <ul class="mt-5 flex-1 space-y-2 text-sm text-slate-600">
        @foreach ($features as $feature)
            <li class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <span>{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    <form method="POST" action="{{ route('cart.items.store') }}" class="mt-6">
        @csrf
        <input type="hidden" name="item_type" value="hosting">
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="billing_cycle" :value="cycle" value="monthly">
        <button type="submit" class="{{ $recommended ? 'btn-primary' : 'btn-secondary' }} w-full">
            Choose {{ $product->name }}
        </button>
    </form>
</div>
