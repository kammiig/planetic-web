@extends('layouts.auth')

@section('title', 'Create your account')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
    <p class="mt-1 text-sm text-slate-500">Start your domain, hosting or website order in minutes.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" novalidate>
        @csrf

        <x-field name="name" label="Full name" autocomplete="name" :required="true" autofocus />
        <x-field name="email" label="Email address" type="email" autocomplete="email" :required="true" />
        <x-field name="phone" label="Phone number" type="tel" autocomplete="tel" help="Optional. Used for order and support contact." />
        <x-field name="company_name" label="Company name" autocomplete="organization" help="Optional." />
        <x-field name="password" label="Password" type="password" autocomplete="new-password" :required="true"
                 help="At least 10 characters with upper &amp; lower case, a number and a symbol." />
        <x-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" :required="true" />

        <div class="flex items-start gap-3">
            <input type="checkbox" id="terms" name="terms" value="1"
                   class="mt-1 h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500"
                   @checked(old('terms'))
                   @error('terms') aria-invalid="true" aria-describedby="terms-error" @enderror>
            <label for="terms" class="text-sm text-slate-600">
                I agree to the <a href="{{ route('legal.terms') }}" class="font-medium text-primary-600 hover:underline">Terms of Use</a>
                and <a href="{{ route('legal.renewal') }}" class="font-medium text-primary-600 hover:underline">Renewal Policy</a>.
            </label>
        </div>
        @error('terms')
            <p id="terms-error" class="field-error" role="alert">{{ $message }}</p>
        @enderror

        <button type="submit" class="btn-primary w-full">Create account</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Sign in</a>
    </p>
@endsection
