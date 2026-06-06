@extends('layouts.customer')

@section('title', $domain->domain_name)
@section('page-title', 'Domain details')

@section('content')
    <a href="{{ route('customer.domains.index') }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to domains</a>

    <div class="mt-4 card-dash">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold">{{ $domain->domain_name }}</h2>
            <x-status-badge :status="$domain->status" />
        </div>

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Registrar</dt><dd class="font-medium">{{ ucfirst($domain->registrar ?? '—') }}</dd></div>
            <div><dt class="text-sm text-slate-500">Registered</dt><dd class="font-medium">{{ $domain->registration_date?->format('j M Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Expiry / renewal</dt><dd class="font-medium">{{ $domain->expiry_date?->format('j M Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Auto-renew</dt><dd class="font-medium">{{ $domain->auto_renew ? 'On' : 'Off' }}</dd></div>
            <div><dt class="text-sm text-slate-500">WHOIS privacy</dt><dd class="font-medium">{{ $domain->whois_privacy ? 'Enabled' : 'Disabled' }}</dd></div>
            <div>
                <dt class="text-sm text-slate-500">DNS (Cloudflare)</dt>
                <dd>
                    @if ($domain->cloudflareZone)
                        <span class="badge badge-success"><span class="badge-dot"></span> {{ ucfirst($domain->cloudflareZone->status) }}</span>
                    @else
                        <span class="badge badge-warning"><span class="badge-dot"></span> DNS activation pending</span>
                    @endif
                </dd>
            </div>
        </dl>

        @if ($domain->nameservers)
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-slate-700">Nameservers</h3>
                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                    @foreach ($domain->nameservers as $ns)
                        <li class="font-mono">{{ $ns }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('customer.domains.dns', $domain) }}" class="btn-secondary btn-sm">View DNS records</a>
        </div>
    </div>
@endsection
