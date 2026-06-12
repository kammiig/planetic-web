<x-emails.layout title="Choose your domain">
    <h1 style="margin:0 0 8px;font-size:22px;">One thing left — your domain</h1>
    <p style="margin:0 0 16px;color:#334155;">Hi {{ $order->user->name }},</p>
    <p style="margin:0 0 16px;color:#334155;">
        Thanks for your order <strong>{{ $order->order_number }}</strong> — payment is confirmed and your
        project is underway. You chose to decide your domain later, so your hosting is waiting for it.
    </p>
    <p style="margin:0 0 16px;color:#334155;">
        Tell us your domain from your dashboard whenever you're ready — register a brand-new one
        (free for the first year with your package) or use a domain you already own. The rest of the
        setup runs automatically the moment you do.
    </p>
    <x-emails.button url="{{ url('/dashboard/website-projects') }}">Choose my domain</x-emails.button>
    <p style="margin:16px 0 0;color:#64748b;font-size:14px;">
        Questions? Just reply to this email or open a support ticket from your dashboard.
    </p>
</x-emails.layout>
