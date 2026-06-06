@extends('layouts.customer')

@section('title', 'Account Settings')
@section('page-title', 'Account Settings')

@section('content')
    <form method="POST" action="{{ route('customer.settings.update') }}" class="max-w-2xl space-y-6" novalidate>
        @csrf
        @method('PUT')

        <div class="card space-y-4">
            <h2 class="text-lg font-bold">Contact details</h2>
            <x-field name="name" label="Full name" :value="$user->name" autocomplete="name" :required="true" />
            <x-field name="email" label="Email address" type="email" :value="$user->email" autocomplete="email" :required="true"
                     help="Changing your email will require re-verification." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="phone" label="Phone" type="tel" :value="$user->phone" autocomplete="tel" />
                <x-field name="company_name" label="Company" :value="$user->company_name" autocomplete="organization" />
            </div>
        </div>

        <div class="card space-y-4">
            <h2 class="text-lg font-bold">Billing address</h2>
            <x-field name="billing_address_line_1" label="Address line 1" :value="$user->billing_address_line_1" autocomplete="address-line1" />
            <x-field name="billing_address_line_2" label="Address line 2" :value="$user->billing_address_line_2" autocomplete="address-line2" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="billing_city" label="City" :value="$user->billing_city" autocomplete="address-level2" />
                <x-field name="billing_state" label="County / State" :value="$user->billing_state" autocomplete="address-level1" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="billing_postcode" label="Postcode" :value="$user->billing_postcode" autocomplete="postal-code" />
                <x-field name="billing_country" label="Country code" :value="$user->billing_country ?? 'GB'" autocomplete="country" help="2-letter code, e.g. GB" />
            </div>
        </div>

        <div class="card space-y-4">
            <h2 class="text-lg font-bold">Change password</h2>
            <p class="text-sm text-slate-500">Leave blank to keep your current password.</p>
            <x-field name="password" label="New password" type="password" autocomplete="new-password"
                     help="At least 10 characters with upper &amp; lower case, a number and a symbol." />
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" />
        </div>

        <button type="submit" class="btn-primary">Save changes</button>
    </form>
@endsection
