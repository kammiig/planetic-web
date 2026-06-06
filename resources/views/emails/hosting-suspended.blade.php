<x-emails.layout title="Hosting suspended">
    <h1 style="margin:0 0 8px;font-size:22px;">Your hosting has been suspended</h1>
    <p style="margin:0 0 16px;color:#334155;">Hi {{ $account->user->name }}, hosting for <strong>{{ $account->domain_name }}</strong> has been suspended because a renewal payment is overdue.</p>
    <p style="margin:0 0 16px;color:#334155;">Your data is safe. To reactivate your hosting, please settle the outstanding balance.</p>
    <x-emails.button url="{{ url('/dashboard/billing') }}">Pay outstanding balance</x-emails.button>
</x-emails.layout>
