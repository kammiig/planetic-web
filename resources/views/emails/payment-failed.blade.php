<x-emails.layout title="Payment could not be completed">
    <h1 style="margin:0 0 8px;font-size:22px;">We couldn't process your payment</h1>
    <p style="margin:0 0 16px;color:#334155;">Hi {{ $order->user->name }}, your payment for order <strong>{{ $order->order_number }}</strong> could not be completed.</p>
    <p style="margin:0 0 16px;color:#334155;">Please check your card details or try another payment method. No services have been affected — you can retry checkout at any time.</p>
    <x-emails.button url="{{ url('/cart') }}">Retry payment</x-emails.button>
    <p style="margin:16px 0 0;color:#64748b;font-size:13px;">If you believe this is a mistake or payment was deducted, please contact our support team.</p>
</x-emails.layout>
