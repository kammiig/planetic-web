@extends('layouts.public')
@section('title', 'Refund Policy')

@section('content')
    <x-legal title="Refund Policy" updated="{{ now()->format('F Y') }}">
        <h2>Domain registrations</h2>
        <p>
            Domain registration fees are <strong>non-refundable</strong> once a domain has been registered. This is because
            the registration is paid immediately to the domain registrar and cannot be reversed. If a domain shown as
            available cannot be registered (for example it was taken moments earlier), we will refund the domain portion or
            help you choose an alternative.
        </p>

        <h2>Hosting</h2>
        <p>
            Hosting fees may be refundable on a pro-rata basis within a reasonable period of purchase where the service has
            not been substantially used. Renewal charges are non-refundable once the new term has begun.
        </p>

        <h2>The £{{ number_format(config('billing.website_package.price'), 0) }} website package</h2>
        <p>
            Because the package includes design work that begins once you submit your project details, refunds are limited
            once work has started. If you request a refund before any design work begins, we will refund the package fee
            less any non-refundable domain registration cost already incurred.
        </p>

        <h2>How to request a refund</h2>
        <p>
            Contact <a href="mailto:{{ config('billing.billing_email') }}">{{ config('billing.billing_email') }}</a> with your
            order number. We will review each request fairly and in line with this policy.
        </p>

        <h2>Chargebacks</h2>
        <p>
            Please contact us before raising a chargeback so we can resolve any issue directly and quickly.
        </p>
    </x-legal>
@endsection
