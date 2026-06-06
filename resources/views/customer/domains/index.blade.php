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
                        <th scope="col">Expiry</th>
                        <th scope="col">DNS</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $domain->domain_name }}</td>
                            <td><x-status-badge :status="$domain->status" /></td>
                            <td>{{ $domain->expiry_date?->format('j M Y') ?? '—' }}</td>
                            <td>
                                @if ($domain->cloudflare_zone_id)
                                    <span class="badge badge-success"><span class="badge-dot"></span> Cloudflare</span>
                                @else
                                    <span class="badge badge-warning"><span class="badge-dot"></span> Pending</span>
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
