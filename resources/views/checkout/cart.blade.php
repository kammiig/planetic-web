@extends('layouts.public')

@section('title', 'Your Cart')

@section('content')
    <section class="container-px py-12">
        <h1 class="text-3xl font-bold">Your cart</h1>

        @if ($cart->items->isEmpty())
            <div class="card mt-8 text-center">
                <p class="text-lg font-semibold text-slate-900">Your cart is empty</p>
                <p class="mt-1 text-slate-500">Search for a domain or choose a plan to get started.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('domains.index') }}" class="btn-primary">Search a domain</a>
                    <a href="{{ route('hosting.index') }}" class="btn-secondary">View hosting</a>
                </div>
            </div>
        @else
            <div class="mt-8 grid gap-8 lg:grid-cols-3">
                {{-- Items --}}
                <div class="lg:col-span-2">
                    <div class="table-wrap">
                        <table class="table-base">
                            <caption class="sr-only">Items in your cart</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Item</th>
                                    <th scope="col" class="text-right">Price</th>
                                    <th scope="col"><span class="sr-only">Remove</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cart->items as $item)
                                    <tr>
                                        <td>
                                            <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                            @if ($item->domain_name)
                                                <p class="text-sm text-slate-500">{{ $item->domain_name }}</p>
                                            @endif
                                            @if ($item->item_type === \App\Enums\ItemType::WebsitePackage)
                                                <p class="mt-1 text-sm text-success">Includes free domain &amp; hosting for the first year</p>
                                            @endif
                                        </td>
                                        <td class="text-right font-semibold">£{{ number_format((float) $item->total, 2) }}</td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-secondary btn-sm" aria-label="Remove {{ $item->name }} from cart">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-6">
                        <strong>Renewal notice.</strong> {{ $freeYearNotice }} Standard domain and hosting renewal charges apply after the first year. See our <a href="{{ route('legal.renewal') }}" class="font-semibold underline">Renewal Policy</a>.
                    </div>
                </div>

                {{-- Summary --}}
                <aside class="lg:col-span-1">
                    <div class="card sticky top-24">
                        <h2 class="text-lg font-bold">Order summary</h2>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-slate-600">Subtotal</dt><dd class="font-medium">£{{ number_format((float) $cart->subtotal, 2) }}</dd></div>
                            @if ((float) $cart->discount_total > 0)
                                <div class="flex justify-between text-success"><dt>Discount</dt><dd>−£{{ number_format((float) $cart->discount_total, 2) }}</dd></div>
                            @endif
                            <div class="mt-2 flex justify-between border-t border-slate-200 pt-3 text-base">
                                <dt class="font-bold">Total due today</dt>
                                <dd class="font-bold">£{{ number_format((float) $cart->total, 2) }}</dd>
                            </div>
                        </dl>
                        <a href="{{ route('checkout.index') }}" class="btn-primary mt-6 w-full">Continue to checkout</a>
                        <a href="{{ route('domains.index') }}" class="mt-3 block text-center text-sm font-medium text-primary-600 hover:underline">Add another domain</a>
                    </div>
                </aside>
            </div>
        @endif
    </section>
@endsection
