@extends('layouts.public')

@section('title', 'Search for a Domain')
@section('meta_description', 'Search and register your domain with WHOIS privacy and automatic Cloudflare DNS setup.')

@section('content')
    <section class="hero-gradient text-white">
        <div class="container-px py-14">
            <div class="mx-auto max-w-3xl text-center">
                <h1 class="text-4xl font-extrabold sm:text-5xl">Find your perfect domain</h1>
                <p class="mt-3 text-slate-300">Search, register and manage your domain — DNS and SSL set up automatically.</p>
            </div>
            <div class="mx-auto mt-8 max-w-3xl rounded-[18px] bg-white/5 p-4 ring-1 ring-white/10">
                <x-domain-search variant="hero" :autofocus="true" />
            </div>
        </div>
    </section>

    {{-- Popular TLDs --}}
    <section class="container-px py-14">
        <h2 class="text-center text-2xl font-bold">Popular extensions</h2>
        <div class="mx-auto mt-6 flex max-w-3xl flex-wrap justify-center gap-3">
            @foreach (['.co.uk' => '9.99', '.com' => '12.99', '.net' => '13.99', '.org' => '12.99', '.uk' => '8.99', '.io' => '39.99'] as $tld => $price)
                <span class="badge badge-neutral text-sm">{{ $tld }} <span class="font-bold text-primary-600">£{{ $price }}/yr</span></span>
            @endforeach
        </div>

        <div class="alert alert-info mx-auto mt-10 max-w-3xl">
            <h3 class="font-bold">About domain renewals</h3>
            <p class="mt-1">Domains are registered for one year and renew annually. We'll remind you well before your renewal date. See the <a href="{{ route('legal.renewal') }}" class="font-semibold underline">Renewal Policy</a>.</p>
        </div>
    </section>
@endsection
