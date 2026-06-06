@extends('layouts.public')
@section('title', 'Renewal Policy')

@section('content')
    <x-legal title="Renewal Policy" updated="{{ now()->format('F Y') }}">
        <h2>First-year inclusive offer</h2>
        <p>
            The £{{ number_format(config('billing.website_package.price'), 0) }} bespoke website package includes a free
            domain and free hosting for the <strong>first year only</strong>. This is a first-year inclusive offer — it is
            <strong>not</strong> free forever. Renewal charges apply after the first year.
        </p>

        <h2>Domain renewals</h2>
        <p>
            Domains are registered for one year and renew annually at the then-current price for the relevant extension
            (TLD). We will send renewal reminders in advance (typically 30, 14, 7, 3 and 1 day before, and on the renewal
            date). Payment is taken before the domain is renewed with the registrar.
        </p>

        <h2>Hosting renewals</h2>
        <p>
            Hosting plans renew on their billing cycle (monthly or yearly) at the standard plan price. If a renewal payment
            fails, we begin a grace period of {{ config('billing.grace_period_days') }} days. If the balance remains unpaid
            after the grace period, hosting may be suspended until payment is received, after which it is reactivated.
        </p>

        <h2>Reminders &amp; auto-renewal</h2>
        <ul>
            <li>We send reminders before each renewal date.</li>
            <li>Where you have a saved payment method, renewals may be charged automatically.</li>
            <li>You can view all renewal dates in your dashboard.</li>
        </ul>

        <h2>Cancellation</h2>
        <p>
            You can choose not to renew a service. Please contact us before the renewal date. See our
            <a href="{{ route('legal.refund') }}">Refund Policy</a> for details on what is and isn't refundable.
        </p>
    </x-legal>
@endsection
