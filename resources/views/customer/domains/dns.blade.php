@extends('layouts.customer')

@section('title', 'DNS · '.$domain->domain_name)
@section('page-title', 'DNS records')

@section('content')
    <a href="{{ route('customer.domains.show', $domain) }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to {{ $domain->domain_name }}</a>

    <p class="mt-3 text-slate-600">DNS records for <strong>{{ $domain->domain_name }}</strong>, managed through Cloudflare.</p>

    @if ($domain->dnsRecords->isEmpty())
        <div class="card mt-4 text-center text-slate-500">DNS records are still being set up. Please check back shortly.</div>
    @else
        <div class="table-wrap mt-4">
            <table class="table-base">
                <caption class="sr-only">DNS records</caption>
                <thead>
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Name</th>
                        <th scope="col">Value</th>
                        <th scope="col">Mode</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domain->dnsRecords as $record)
                        <tr>
                            <td class="font-semibold">{{ $record->type }}</td>
                            <td class="font-mono text-sm">{{ $record->name }}</td>
                            <td class="font-mono text-sm break-all">{{ $record->content }}</td>
                            <td>
                                @if ($record->proxied)
                                    <span class="badge badge-primary"><span class="badge-dot"></span> Proxied</span>
                                @else
                                    <span class="badge badge-neutral"><span class="badge-dot"></span> DNS only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-sm text-slate-500">Website records are proxied for speed and security. Mail and control-panel records are DNS-only so email keeps working.</p>
    @endif
@endsection
