@props([
    'variant' => 'page', // 'hero' on dark backgrounds, 'page' on light
    'autofocus' => false,
])

@php $dark = $variant === 'hero'; @endphp

<div
    x-data="{
        query: @js(request('q', '')),
        loading: false,
        error: '',
        result: null,
        suggestions: [],
        adding: null,
        async search() {
            this.error = ''; this.result = null; this.suggestions = [];
            const domain = this.query.trim().toLowerCase();
            if (!domain) { this.error = 'Please enter a domain name to search.'; return; }
            this.loading = true;
            try {
                const res = await fetch(@js(route('domains.search')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ domain }),
                });
                const data = await res.json();
                if (!res.ok || data.success === false) {
                    this.error = data.message || 'We could not check this domain right now. Please try again in a few moments.';
                } else {
                    this.result = data;
                    this.suggestions = data.suggestions || [];
                }
            } catch (e) {
                this.error = 'We could not check this domain right now. Please try again in a few moments.';
            } finally {
                this.loading = false;
            }
        },
        async addToCart(domain) {
            this.adding = domain;
            try {
                const res = await fetch(@js(route('cart.items.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ item_type: 'domain_registration', domain_name: domain }),
                });
                if (res.ok) { window.location = @js(route('cart.index')); }
                else { this.error = 'We could not add that domain to your cart. Please try again.'; }
            } catch (e) {
                this.error = 'We could not add that domain to your cart. Please try again.';
            } finally {
                this.adding = null;
            }
        },
    }"
    class="w-full"
>
    <form @submit.prevent="search" class="flex flex-col gap-3 sm:flex-row" role="search" aria-label="Domain availability search">
        <div class="flex-1">
            <label for="domain-q-{{ $variant }}" class="{{ $dark ? 'sr-only' : 'label' }}">Domain name</label>
            <input
                id="domain-q-{{ $variant }}"
                type="text"
                x-model="query"
                @if ($autofocus) autofocus @endif
                inputmode="url"
                autocomplete="off"
                spellcheck="false"
                placeholder="yourbusiness.co.uk"
                class="input {{ $dark ? 'border-transparent shadow-large' : '' }}"
                aria-describedby="domain-q-help-{{ $variant }}"
            >
            <p id="domain-q-help-{{ $variant }}" class="{{ $dark ? 'mt-2 text-sm text-slate-300' : 'help-text' }}">
                Search for your business domain. Renewal applies after the first year.
            </p>
        </div>
        <div class="{{ $dark ? '' : 'sm:pt-7' }}">
            <button type="submit" class="btn-primary w-full sm:w-auto" :disabled="loading">
                <span x-show="!loading">Search Domain</span>
                <span x-show="loading" x-cloak>Checking domain availability…</span>
            </button>
        </div>
    </form>

    {{-- Results region — announced to screen readers --}}
    <div class="mt-4" aria-live="polite" role="status">
        <template x-if="error">
            <div class="alert alert-danger flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <span x-text="error"></span>
            </div>
        </template>

        <template x-if="result && result.available">
            <div class="rounded-[14px] border border-success bg-white p-4 text-slate-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-success" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <p><span class="font-semibold" x-text="result.domain"></span> is <span class="font-semibold text-success">available</span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold" x-text="result.premium ? 'Premium · £' + result.price + '/yr' : '£' + result.price + '/yr'"></span>
                        <button type="button" class="btn-primary btn-sm" @click="addToCart(result.domain)" :disabled="adding === result.domain">
                            <span x-show="adding !== result.domain">Add to Cart</span>
                            <span x-show="adding === result.domain" x-cloak>Adding…</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="result && !result.available">
            <div class="rounded-[14px] border border-slate-200 bg-white p-4 text-slate-900">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-danger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
                    <p><span class="font-semibold" x-text="result.domain"></span> is not available.</p>
                </div>
                <template x-if="suggestions.length">
                    <div class="mt-3">
                        <p class="text-sm font-medium text-slate-600">Try one of these alternatives:</p>
                        <ul class="mt-2 divide-y divide-slate-200">
                            <template x-for="s in suggestions" :key="s.domain">
                                <li class="flex items-center justify-between py-2">
                                    <span x-text="s.domain + ' — £' + s.price + '/yr'"></span>
                                    <button type="button" class="btn-secondary btn-sm" @click="addToCart(s.domain)" :disabled="adding === s.domain">
                                        <span x-show="adding !== s.domain">Add to Cart</span>
                                        <span x-show="adding === s.domain" x-cloak>Adding…</span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
