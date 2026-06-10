@php
    use App\Enums\ItemType;
    $subtotal = $lineItems->sum(fn ($i) => (float) $i->total);
    $vat = max(0, (float) $total - $subtotal);
    $showPay = $showPay ?? false;
@endphp

<div class="overflow-hidden rounded-[18px] border border-slate-200 bg-white shadow-soft lg:sticky lg:top-24">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="text-lg font-bold">Summary</h2>
    </div>

    <div class="px-5 py-4">
        <p class="text-sm text-slate-500">{{ $lineItems->count() }} {{ Str::plural('Item', $lineItems->count()) }}</p>

        <ul class="mt-4 space-y-4">
            @foreach ($lineItems as $item)
                <li class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                        @if ($item->domain_name)
                            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $item->domain_name }}</p>
                        @endif
                        @if ($item->item_type === ItemType::WebsitePackage)
                            <p class="mt-1 inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-0.5 text-xs font-semibold text-success">Free domain &amp; hosting · year 1</p>
                        @elseif ($item->item_type === ItemType::Hosting)
                            <p class="mt-0.5 text-sm text-slate-500">Managed cPanel hosting</p>
                        @endif
                    </div>
                    <p class="whitespace-nowrap font-semibold text-slate-900">£{{ number_format((float) $item->total, 2) }}</p>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="space-y-2 border-t border-slate-200 px-5 py-4 text-sm">
        <div class="flex justify-between text-slate-600">
            <span>Subtotal</span>
            <span class="font-medium text-slate-900">£{{ number_format($subtotal, 2) }}</span>
        </div>
        <div class="flex justify-between text-slate-600">
            <span>VAT</span>
            <span class="font-medium text-slate-900">£{{ number_format($vat, 2) }}</span>
        </div>
    </div>

    <div class="border-t border-slate-200 px-5 py-4">
        <div class="flex items-center justify-between">
            <span class="text-lg font-bold">Total due today</span>
            <span class="text-2xl font-extrabold">£{{ number_format((float) $total, 2) }}</span>
        </div>

        @if ($showPay)
            {{-- Pay button lives in the summary (reference layout); enabled once the
                 Payment Element is mounted on the Payment step. --}}
            <button type="button" @click="pay()" x-bind:disabled="paying || !paymentReady"
                    class="btn-primary mt-4 w-full">
                <span x-show="!paying" class="inline-flex items-center gap-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Pay £{{ number_format((float) $total, 2) }} now
                </span>
                <span x-show="paying" x-cloak class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4z"/></svg>
                    Processing payment…
                </span>
            </button>
            <p x-show="!paymentReady" class="mt-2 text-center text-xs text-slate-500">Complete the steps to enable payment.</p>
        @endif

        @if ($freeYearNotice)
            <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ $freeYearNotice }} Your services renew based on your billing cycle unless cancelled.</p>
        @endif
    </div>

    <div class="border-t border-success/30 bg-success/5 px-5 py-4 text-center text-sm text-success">
        <p class="inline-flex items-center gap-1.5 font-medium">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Secure SSL encrypted payment
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-2 border-t border-slate-200 px-5 py-4" aria-label="Accepted payment methods">
        @foreach (['Visa', 'Mastercard', 'Amex', 'Apple Pay', 'Google Pay'] as $brand)
            <span class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $brand }}</span>
        @endforeach
    </div>
</div>
