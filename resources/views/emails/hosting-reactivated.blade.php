<x-emails.layout title="Hosting reactivated">
    <h1 style="margin:0 0 8px;font-size:22px;">Your hosting is active again ✓</h1>
    <p style="margin:0 0 16px;color:#334155;">Hi {{ $account->user->name }}, thanks for your payment. Hosting for <strong>{{ $account->domain_name }}</strong> has been reactivated and your website is back online.</p>
    <x-emails.button url="{{ url('/dashboard/hosting') }}">View hosting</x-emails.button>
</x-emails.layout>
