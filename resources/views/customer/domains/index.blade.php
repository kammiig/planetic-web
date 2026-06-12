@extends('layouts.customer')

@section('title', 'Domains')
@section('page-title', 'Domains')

@section('content')
    @if ($domains->isEmpty())
        <div class="card text-center">
            <p class="text-lg font-semibold">You do not have any domains yet.</p>
            <p class="mt-1 text-slate-500">Search and register your first domain to get started.</p>
            <a href="{{ route('domains.index') }}" class="btn-primary mt-6">Search a domain</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="table-base">
                <caption class="sr-only">Your domains</caption>
                <thead>
                    <tr>
                        <th scope="col">Domain</th>
                        <th scope="col">Status</th>
                        <th scope="col">Order</th>
                        <th scope="col">Registered</th>
                        <th scope="col">Expiry</th>
                        <th scope="col">Price</th>
                        <th scope="col">Cloudflare</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        @php
                            $item = $domain->order?->items->firstWhere('domain_name', $domain->domain_name);
                            $isWebsiteBundle = $item && $item->item_type === \App\Enums\ItemType::WebsitePackage;
                        @endphp
                        <tr>
                            <td class="font-semibold text-slate-900">
                                {{ $domain->domain_name }}
                                @if ($domain->registrar === 'external')
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">External</span>
                                @endif
                            </td>
                            <td>
                                <x-status-badge :status="$domain->status" />
                                @if (in_array($domain->status, [\App\Enums\DomainStatus::Failed, \App\Enums\DomainStatus::ManualReview, \App\Enums\DomainStatus::RegistrationPending], true))
                                    <p class="mt-1 text-xs text-slate-500">{{ $domain->status->customerLabel() }}</p>
                                @endif
                            </td>
                            <td>{{ $domain->order?->order_number ?? '—' }}</td>
                            <td>{{ $domain->registration_date?->format('j M Y') ?? '—' }}</td>
                            <td>{{ $domain->expiry_date?->format('j M Y') ?? '—' }}</td>
                            <td>
                                @if ($isWebsiteBundle)
                                    Included
                                @elseif ($item)
                                    £{{ number_format((float) $item->unit_price, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($domain->registrar === 'external')
                                    <span class="text-xs text-slate-500">Your registrar</span>
                                @else
                                    {{ $domain->cloudflareZone?->status?->label() ?? '—' }}
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('customer.domains.show', $domain) }}" class="btn-secondary btn-sm">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $domains->links() }}</div>
    @endif
@endsection
