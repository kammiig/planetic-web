<x-emails.layout title="We're completing your setup">
    <h1 style="margin:0 0 8px;font-size:22px;">We're completing your setup</h1>
    <p style="margin:0 0 16px;color:#334155;">Hi {{ $order->user->name }},</p>
    <p style="margin:0 0 16px;color:#334155;">
        Thanks for your order <strong>{{ $order->order_number }}</strong> — your payment was received successfully.
        One of the automated setup steps needs a quick check from our team, so we're finishing it for you by hand.
    </p>
    <p style="margin:0 0 16px;color:#334155;">
        <strong>You don't need to do anything.</strong> Your services are listed in your dashboard and will show as
        active as soon as setup is complete — usually within a few hours. We'll email you the moment everything is ready.
    </p>
    <x-emails.button url="{{ url('/dashboard') }}">View your dashboard</x-emails.button>
    <p style="margin:16px 0 0;color:#64748b;font-size:14px;">
        Questions? Just reply to this email or open a support ticket from your dashboard.
    </p>
</x-emails.layout>
