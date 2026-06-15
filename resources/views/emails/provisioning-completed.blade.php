<x-emails.layout title="Your services are ready">
    <h1 style="margin:0 0 8px;font-size:22px;">Your services are ready! 🎉</h1>
    <p style="margin:0 0 16px;color:#334155;">Good news, {{ $order->user->name }} — your order <strong>{{ $order->order_number }}</strong> has been set up.</p>

    @if ($domain)
        <p style="margin:0 0 8px;color:#334155;">✓ Domain <strong>{{ $domain->domain_name }}</strong> is set up and pointed to Cloudflare.</p>
    @endif

    @if ($hosting)
        <p style="margin:16px 0 8px;color:#334155;">✓ Your hosting is active. Here are your account details:</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border-collapse:collapse;">
            @php
                $rows = array_filter([
                    'Domain' => $hosting->domain_name,
                    'Hosting plan' => $hosting->hostingPackage?->name,
                    'cPanel username' => $hosting->whm_username,
                    'Server IP' => $hosting->server_ip,
                    'Server hostname' => $hosting->server_hostname,
                    'DNS / SSL' => $domain?->cloudflareZone?->dnsStatusLabel(),
                ]);
            @endphp
            @foreach ($rows as $label => $value)
                <tr>
                    <td style="padding:6px 12px 6px 0;color:#64748b;font-size:14px;white-space:nowrap;vertical-align:top;">{{ $label }}</td>
                    <td style="padding:6px 0;color:#0f172a;font-size:14px;font-weight:600;">{{ $value }}</td>
                </tr>
            @endforeach
            @if ($domain?->nameservers)
                <tr>
                    <td style="padding:6px 12px 6px 0;color:#64748b;font-size:14px;white-space:nowrap;vertical-align:top;">Nameservers</td>
                    <td style="padding:6px 0;color:#0f172a;font-size:14px;font-weight:600;">{{ implode(' · ', $domain->nameservers) }}</td>
                </tr>
            @endif
        </table>

        <p style="margin:0 0 8px;color:#334155;">
            <strong>Logging in:</strong> open cPanel straight from your dashboard with one click —
            no password to remember. You'll be securely signed in automatically.
        </p>
        <x-emails.button url="{{ url('/dashboard/hosting') }}">Open my hosting &amp; cPanel</x-emails.button>
        <p style="margin:12px 0 0;color:#64748b;font-size:13px;">
            For security we don't email passwords. If you'd like a direct password to log in elsewhere,
            set one inside cPanel under <em>Password &amp; Security</em> after your first one-click login.
        </p>
    @endif

    @if ($order->containsWebsitePackage())
        <p style="margin:16px 0;color:#334155;">Next step: complete your website project intake so our team can start building your bespoke website.</p>
        <x-emails.button url="{{ url('/dashboard/website-projects') }}">Complete your project details</x-emails.button>
    @elseif (! $hosting)
        <x-emails.button url="{{ url('/dashboard') }}">View your services</x-emails.button>
    @endif
</x-emails.layout>
