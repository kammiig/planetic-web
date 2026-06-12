@extends('layouts.customer')

@section('title', 'Website Projects')
@section('page-title', 'Website Projects')

@section('content')
    @if ($projects->isEmpty())
        <div class="card text-center">
            <p class="text-lg font-semibold">No website projects yet.</p>
            <p class="mt-1 text-slate-500">Order the £{{ number_format(config('billing.website_package.price'), 0) }} website package to start a project.</p>
            <a href="{{ route('website-package') }}" class="btn-primary mt-6">Get a website</a>
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($projects as $project)
                <a href="{{ route('customer.projects.show', $project) }}" class="card-dash transition hover:shadow-soft">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">{{ $project->business_name ?? 'Website project' }}</p>
                            <p class="text-sm text-slate-500">{{ $project->project_number }}</p>
                        </div>
                        <x-status-badge :status="$project->status" />
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm sm:grid-cols-4">
                        <div><dt class="text-slate-500">Order</dt><dd class="font-medium">{{ $project->order?->order_number ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Price paid</dt><dd class="font-medium">{{ $project->order ? '£'.number_format((float) $project->order->total, 2) : '—' }}</dd></div>
                        <div><dt class="text-slate-500">Ordered</dt><dd class="font-medium">{{ $project->order?->paid_at?->format('j M Y') ?? $project->created_at?->format('j M Y') ?? '—' }}</dd></div>
                        <div>
                            <dt class="text-slate-500">Domain</dt>
                            <dd class="font-medium">
                                @if ($name = $project->domain?->domain_name ?? $project->order?->primaryDomainName())
                                    {{ $name }}
                                @else
                                    <span class="text-amber-700">Waiting for domain</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    @if ($hosting = $project->order?->hostingAccount)
                        <p class="mt-2 text-sm text-slate-500">Hosting: <span class="font-medium text-slate-700">{{ $hosting->status->customerLabel() }}</span></p>
                    @endif
                    @if ($action = $project->status->customerNextAction())
                        <p class="mt-3 text-sm text-warning">{{ $action }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
@endsection
