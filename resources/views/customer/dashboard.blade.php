@extends('layouts.customer')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <p class="text-slate-600">Welcome back, {{ auth()->user()->name }}. Here's an overview of your services.</p>

    {{-- In-progress / manual review notices --}}
    @foreach ($inProgressOrders as $order)
        <div class="alert {{ $order->status === \App\Enums\OrderStatus::ManualReview ? 'alert-info' : 'alert-warning' }} mt-4">
            <strong>Order {{ $order->order_number }}:</strong>
            {{ $order->status->customerLabel() }}.
            @if ($order->status === \App\Enums\OrderStatus::ManualReview)
                Our team has been notified and will complete your setup shortly.
            @else
                We're setting up your services — this usually only takes a few minutes.
            @endif
        </div>
    @endforeach

    {{-- Stat cards (active count + a pending hint so paid services are never "0") --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Active domains', 'value' => $domainsCount, 'pending' => $pendingDomainsCount, 'href' => route('customer.domains.index'), 'icon' => 'M3 12h18M12 3a15 15 0 0 1 0 18M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z'],
            ['label' => 'Active hosting', 'value' => $hostingCount, 'pending' => $pendingHostingCount, 'href' => route('customer.hosting.index'), 'icon' => 'M3 5h18v6H3zM3 13h18v6H3z'],
            ['label' => 'Website projects', 'value' => $projectsCount, 'pending' => 0, 'href' => route('customer.projects.index'), 'icon' => 'M4 4h16v12H4zM2 20h20M9 8h6M9 12h6'],
            ['label' => 'Open invoices', 'value' => $openInvoicesCount, 'pending' => 0, 'href' => route('customer.billing.index'), 'icon' => 'M3 6h18v12H3zM3 10h18'],
            ['label' => 'Open tickets', 'value' => $openTicketsCount, 'pending' => 0, 'href' => route('customer.support.index'), 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
        ] as $stat)
            <a href="{{ $stat['href'] }}" class="card-dash transition hover:shadow-soft">
                <div class="flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-[10px] bg-primary-50 text-primary-600" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="{{ $stat['icon'] }}"/></svg>
                    </span>
                    <span class="text-3xl font-extrabold text-slate-900">{{ $stat['value'] }}</span>
                </div>
                <p class="mt-3 text-sm font-medium text-slate-600">
                    {{ $stat['label'] }}
                    @if ($stat['pending'] > 0)
                        <span class="ml-1 inline-flex items-center rounded-full bg-warning/15 px-2 py-0.5 text-xs font-semibold text-amber-700">+{{ $stat['pending'] }} pending</span>
                    @endif
                </p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Next renewal + website project --}}
        <div class="card-dash">
            <h2 class="text-lg font-bold">Next renewal</h2>
            @if ($nextRenewal)
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ \Illuminate\Support\Carbon::parse($nextRenewal)->format('j M Y') }}</p>
                <p class="mt-1 text-sm text-slate-500">We'll remind you before anything renews.</p>
            @else
                <p class="mt-2 text-slate-500">No upcoming renewals.</p>
            @endif
            <a href="{{ route('customer.billing.index') }}" class="mt-4 inline-block text-sm font-semibold text-primary-600 hover:underline">View billing →</a>
        </div>

        <div class="card-dash">
            <h2 class="text-lg font-bold">Website project</h2>
            @if ($project)
                <div class="mt-2 flex items-center gap-3">
                    <x-status-badge :status="$project->status" />
                    <span class="text-sm text-slate-500">{{ $project->project_number }}</span>
                </div>
                @if ($action = $project->status->customerNextAction())
                    <p class="mt-3 text-sm text-warning">{{ $action }}</p>
                @endif
                <a href="{{ route('customer.projects.show', $project) }}" class="mt-4 inline-block text-sm font-semibold text-primary-600 hover:underline">View project →</a>
            @else
                <p class="mt-2 text-slate-500">No website project yet.</p>
                <a href="{{ route('website-package') }}" class="mt-4 inline-block text-sm font-semibold text-primary-600 hover:underline">Get a website →</a>
            @endif
        </div>
    </div>
@endsection
