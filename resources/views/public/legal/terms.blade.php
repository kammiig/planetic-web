@extends('layouts.public')
@section('title', 'Terms of Use')

@section('content')
    <x-legal title="Terms of Use" updated="{{ now()->format('F Y') }}">
        <p>By using Planetic Web and purchasing our services you agree to these terms.</p>

        <h2>Services</h2>
        <p>We provide domain registration, hosting, DNS management, bespoke website design and related services. Service availability and features are described on the relevant pages of this site.</p>

        <h2>Accounts</h2>
        <ul>
            <li>You must provide accurate information and keep your login secure.</li>
            <li>You are responsible for activity carried out under your account.</li>
            <li>We may suspend accounts that breach these terms or remain unpaid.</li>
        </ul>

        <h2>Payment &amp; renewals</h2>
        <p>Prices are shown in GBP and calculated at checkout. Services renew as described in our <a href="{{ route('legal.renewal') }}">Renewal Policy</a>. The £{{ number_format(config('billing.website_package.price'), 0) }} website package includes a free domain and hosting for the first year only; renewal charges apply afterwards.</p>

        <h2>Acceptable use</h2>
        <p>You must not use our services for unlawful, abusive, or harmful activity, or in a way that disrupts our infrastructure or other customers.</p>

        <h2>Provisioning &amp; third parties</h2>
        <p>Domains, hosting and DNS are provisioned through third-party providers. Where automated provisioning cannot complete, our team will review and complete it manually.</p>

        <h2>Liability</h2>
        <p>We take reasonable care to keep services available and secure, but provide them "as is" to the extent permitted by law. Our liability is limited to the fees paid for the affected service.</p>

        <h2>Contact</h2>
        <p>Questions about these terms? Email <a href="mailto:{{ config('billing.support_email') }}">{{ config('billing.support_email') }}</a>.</p>
    </x-legal>
@endsection
