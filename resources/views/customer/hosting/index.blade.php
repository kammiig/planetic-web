@extends('layouts.customer')

@section('title', 'Hosting')
@section('page-title', 'Hosting')

@section('content')
    @if ($accounts->isEmpty())
        <div class="card text-center">
            <p class="text-lg font-semibold">You don't have any hosting accounts yet.</p>
            <p class="mt-1 text-slate-500">Choose a plan to get started.</p>
            <a href="{{ route('hosting.index') }}" class="btn-primary mt-6">View hosting plans</a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($accounts as $account)
                <div class="card-dash">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-slate-900">{{ $account->domain_name }}</h2>
                        <x-status-badge :status="$account->status" />
                    </div>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Plan</dt><dd class="font-medium">{{ $account->hostingPackage?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Renewal</dt><dd class="font-medium">{{ $account->renewal_date?->format('j M Y') ?? '—' }}</dd></div>
                    </dl>
                    <a href="{{ route('customer.hosting.show', $account) }}" class="btn-secondary btn-sm mt-4">Manage</a>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $accounts->links() }}</div>
    @endif
@endsection
