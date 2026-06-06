@extends('layouts.public')
@section('title', 'Privacy Policy')

@section('content')
    <x-legal title="Privacy Policy" updated="{{ now()->format('F Y') }}">
        <p>This policy explains what personal data Planetic Web collects, why we collect it, and how we protect it.</p>

        <h2>What we collect</h2>
        <ul>
            <li>Account details: name, email, phone and company name.</li>
            <li>Billing details: billing address and payment references (we never store full card numbers or CVV).</li>
            <li>Domain contact details required by registrars to register domains.</li>
            <li>Website project details and files you upload.</li>
            <li>Support messages you send us.</li>
        </ul>

        <h2>How we use it</h2>
        <p>We use your data only to provide and support your services — registering domains, creating hosting, processing payments, sending service emails and handling support.</p>

        <h2>Payments</h2>
        <p>Payments are processed securely by Stripe. We store only the references Stripe gives us (such as customer and payment IDs), never your card number or security code.</p>

        <h2>Third parties</h2>
        <p>To deliver your services we share the minimum necessary data with our domain registrar (NameSilo/Namecheap), hosting (cPanel/WHM), DNS provider (Cloudflare) and payment provider (Stripe).</p>

        <h2>Security</h2>
        <p>Access to your records is restricted to you and authorised staff. API keys and secrets are stored securely and never exposed publicly.</p>

        <h2>Your rights &amp; data deletion</h2>
        <p>You can request access to, correction of, or deletion of your personal data. Where you have active services we may need to cancel those first, and we keep records required for legal and accounting purposes. Contact <a href="mailto:{{ config('billing.support_email') }}">{{ config('billing.support_email') }}</a>.</p>
    </x-legal>
@endsection
