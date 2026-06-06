<x-emails.layout title="Order confirmation">
    <h1 style="margin:0 0 8px;font-size:22px;">Thanks for your order, {{ $order->user->name }}!</h1>
    <p style="margin:0 0 16px;color:#334155;">We've received your payment and your order is confirmed. Order number <strong>{{ $order->order_number }}</strong>.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        @foreach ($order->items as $item)
            <tr>
                <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9;">{{ $item->name }}@if ($item->domain_name)<br><span style="color:#64748b;font-size:13px;">{{ $item->domain_name }}</span>@endif</td>
                <td style="padding:12px 16px;border-bottom:1px solid #f1f5f9;text-align:right;white-space:nowrap;">£{{ number_format((float) $item->total, 2) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:12px 16px;font-weight:700;">Total paid</td>
            <td style="padding:12px 16px;text-align:right;font-weight:700;">£{{ number_format((float) $order->total, 2) }}</td>
        </tr>
    </table>

    @if ($order->containsWebsitePackage())
        <p style="margin:16px 0 0;color:#16a34a;font-weight:600;">Your domain and hosting are included free for the first year. Renewal applies after the first year.</p>
    @endif

    <p style="margin:16px 0;color:#334155;">We're now setting up your services automatically. You can track progress in your dashboard.</p>
    <x-emails.button url="{{ url('/dashboard') }}">Go to your dashboard</x-emails.button>
</x-emails.layout>
