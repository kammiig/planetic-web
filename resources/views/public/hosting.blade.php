@extends('layouts.public')

@section('title', 'Hosting Packages')
@section('meta_description', 'Fast, secure cPanel hosting with free SSL and Cloudflare DNS. Starter, Business, Pro and Agency plans.')

@section('content')
    <section class="hero-gradient text-white">
        <div class="container-px py-14 text-center">
            <h1 class="text-4xl font-extrabold sm:text-5xl">Hosting that just works</h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-300">Free SSL, cPanel and automatic Cloudflare DNS on every plan. Upgrade any time.</p>
        </div>
    </section>

    <section class="container-px py-16" x-data="{ cycle: 'monthly' }">
        <div class="flex justify-center">
            <div class="inline-flex rounded-full border border-slate-200 bg-white p-1" role="group" aria-label="Billing cycle">
                <button type="button" @click="cycle = 'monthly'" :class="cycle === 'monthly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-5 py-2 text-sm font-semibold">Monthly</button>
                <button type="button" @click="cycle = 'yearly'" :class="cycle === 'yearly' ? 'bg-primary-500 text-white' : 'text-slate-600'" class="rounded-full px-5 py-2 text-sm font-semibold">Yearly</button>
            </div>
        </div>

        @if ($plans->isEmpty())
            <p class="mt-10 text-center text-slate-500">Hosting plans are being set up. Please check back shortly.</p>
        @else
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($plans as $i => $plan)
                    <x-hosting-plan-card :product="$plan" :recommended="$i === 1" />
                @endforeach
            </div>
        @endif

        <p class="mt-8 text-center text-sm text-slate-500">
            Need a website too? The <a href="{{ route('website-package') }}" class="font-semibold text-primary-600 hover:underline">£200 website package</a> includes free hosting for the first year.
        </p>
    </section>

    <section class="bg-slate-50 py-16">
        <div class="container-px grid gap-6 md:grid-cols-3">
            @foreach ([
                ['Free SSL', 'Every site is secured with a free, auto-renewing SSL certificate.'],
                ['Cloudflare DNS', 'We configure Cloudflare DNS, proxy and Always-Use-HTTPS for you.'],
                ['cPanel included', 'Manage email, files and databases with the familiar cPanel interface.'],
            ] as [$t, $d])
                <div class="card">
                    <h3 class="text-lg font-bold">{{ $t }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endsection
