<x-emails.layout title="Your services are ready">
    <h1 style="margin:0 0 8px;font-size:22px;">Your services are ready! 🎉</h1>
    <p style="margin:0 0 16px;color:#334155;">Good news, {{ $order->user->name }} — your order <strong>{{ $order->order_number }}</strong> has been set up.</p>

    @if ($domain)
        <p style="margin:0 0 8px;color:#334155;">✓ Domain <strong>{{ $domain->domain_name }}</strong> is registered and pointed to Cloudflare.</p>
    @endif
    @if ($hosting)
        <p style="margin:0 0 8px;color:#334155;">✓ Hosting is active. cPanel username: <strong>{{ $hosting->whm_username }}</strong>.</p>
    @endif

    @if ($order->containsWebsitePackage())
        <p style="margin:16px 0;color:#334155;">Next step: complete your website project intake form so our team can start building your bespoke website.</p>
        <x-emails.button url="{{ url('/dashboard/website-projects') }}">Complete your project details</x-emails.button>
    @else
        <x-emails.button url="{{ url('/dashboard') }}">View your services</x-emails.button>
    @endif
</x-emails.layout>
