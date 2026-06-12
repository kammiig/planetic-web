{{-- Late domain choice for paid orders placed with "I'll decide later".
     Expects: $order (paid, owned by the viewer, no domain yet). --}}
<div class="card mt-6 border-warning/40" x-data="{ source: '{{ old('domain_source', 'new') }}' }">
    <h2 class="text-lg font-bold">Choose the domain for order {{ $order->order_number }}</h2>
    <p class="mt-1 text-sm text-slate-600">
        Your services are paid for and waiting — tell us the domain and we'll register it (if new),
        set up DNS and create your hosting automatically.
    </p>

    <form method="POST" action="{{ route('customer.orders.domain', $order) }}" class="mt-4 space-y-4" novalidate>
        @csrf

        <div class="space-y-3" role="radiogroup" aria-label="Domain options">
            <label class="flex cursor-pointer items-start gap-3 rounded-[12px] border p-4 transition"
                   :class="source === 'new' ? 'border-primary-500 bg-primary-50/40' : 'border-slate-200 hover:border-slate-300'">
                <input type="radio" name="domain_source" value="new" x-model="source"
                       class="mt-1 h-5 w-5 border-slate-300 text-primary-500 focus:ring-primary-500">
                <span>
                    <span class="block font-semibold text-slate-900">Register a new domain — free for the first year</span>
                    <span class="mt-0.5 block text-sm text-slate-500">Included with your website package. Renewal applies after the first year.</span>
                </span>
            </label>

            <label class="flex cursor-pointer items-start gap-3 rounded-[12px] border p-4 transition"
                   :class="source === 'existing' ? 'border-primary-500 bg-primary-50/40' : 'border-slate-200 hover:border-slate-300'">
                <input type="radio" name="domain_source" value="existing" x-model="source"
                       class="mt-1 h-5 w-5 border-slate-300 text-primary-500 focus:ring-primary-500">
                <span>
                    <span class="block font-semibold text-slate-900">Use a domain I already own</span>
                    <span class="mt-0.5 block text-sm text-slate-500">Keep it where it's registered — we'll connect everything and show you the DNS settings.</span>
                </span>
            </label>
        </div>

        <x-field name="domain_name" label="Domain name" :required="true" placeholder="yourbusiness.com"
                 help="e.g. yourbusiness.com — we'll check availability automatically for new registrations." />

        <button type="submit" class="btn-primary">Save domain &amp; start setup</button>
    </form>
</div>
