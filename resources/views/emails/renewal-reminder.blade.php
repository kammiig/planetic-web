<x-emails.layout title="Renewal reminder">
    <h1 style="margin:0 0 8px;font-size:22px;">Your {{ $serviceName }} renews soon</h1>
    <p style="margin:0 0 16px;color:#334155;">Hi {{ $customerName }}, this is a friendly reminder that your service is due to renew
        @if ($daysBefore > 0) in <strong>{{ $daysBefore }} {{ \Illuminate\Support\Str::plural('day', $daysBefore) }}</strong> @else <strong>today</strong> @endif
        on <strong>{{ $renewalDate }}</strong>.</p>

    @if ($amount)
        <p style="margin:0 0 16px;color:#334155;">Renewal amount: <strong>£{{ number_format((float) $amount, 2) }}</strong>.</p>
    @endif

    <p style="margin:0 0 16px;color:#334155;">To keep your service active, please ensure your payment method is up to date.</p>
    <x-emails.button url="{{ url('/dashboard/billing') }}">Review billing</x-emails.button>
</x-emails.layout>
