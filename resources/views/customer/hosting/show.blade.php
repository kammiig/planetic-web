@extends('layouts.customer')

@section('title', $account->domain_name ?? 'Hosting account')
@section('page-title', 'Hosting account')

@section('content')
    <a href="{{ route('customer.hosting.index') }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to hosting</a>

    <div class="mt-4 card-dash">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-bold">{{ $account->domain_name ?? 'Waiting for your domain' }}</h2>
            <x-status-badge :status="$account->status" />
        </div>

        @if ($account->status === \App\Enums\HostingStatus::AwaitingDomain && $account->order)
            <div class="alert alert-warning mt-4">
                Action needed: tell us your domain below and we'll finish setting up your hosting automatically.
            </div>
        @endif

        @if ($account->isSuspended() && $account->suspension_reason)
            <div class="alert alert-warning mt-4">This account is suspended. {{ $account->suspension_reason }}</div>
        @endif

        <dl class="mt-6 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Plan</dt><dd class="font-medium">{{ $account->hostingPackage?->name ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Username</dt><dd class="font-mono font-medium">{{ $account->whm_username ?? 'Assigned with your domain' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Cloudflare DNS</dt><dd class="font-medium">{{ $account->domain?->cloudflareZone?->dnsStatusLabel() ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">SSL</dt><dd class="font-medium">{{ $account->domain?->cloudflareZone?->sslStatusLabel() ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Server IP</dt><dd class="font-mono font-medium">{{ $account->server_ip ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Renewal</dt><dd class="font-medium">{{ $account->renewal_date?->format('j M Y') ?? '—' }}</dd></div>
            @if ($account->disk_limit_mb)
                <div><dt class="text-sm text-slate-500">Storage</dt><dd class="font-medium">{{ $account->hostingPackage?->diskLabel() ?? ($account->disk_limit_mb.' MB') }}</dd></div>
            @endif
        </dl>

        @if ($account->cpanel_url && $account->isActive())
            <div class="mt-6">
                <a href="{{ $account->cpanel_url }}" target="_blank" rel="noopener" class="btn-primary btn-sm">Open cPanel</a>
                <p class="mt-2 text-xs text-slate-500">Opens your cPanel control panel in a new tab.</p>
            </div>
        @endif
    </div>

    @if ($account->status === \App\Enums\HostingStatus::AwaitingDomain && $account->order && $account->order->isPaid())
        @include('customer.partials.add-domain-form', ['order' => $account->order])
    @endif
@endsection
