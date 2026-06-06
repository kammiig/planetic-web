@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Sign in</h1>
    <p class="mt-1 text-sm text-slate-500">Access your domains, hosting, billing and support.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" novalidate>
        @csrf

        <x-field name="email" label="Email address" type="email" autocomplete="email" :required="true" autofocus />
        <x-field name="password" label="Password" type="password" autocomplete="current-password" :required="true" />

        <div class="flex items-center justify-between">
            <label for="remember" class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" id="remember" name="remember"
                       class="h-5 w-5 rounded border-slate-300 text-primary-500 focus:ring-primary-500">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:underline">Forgot password?</a>
        </div>

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        New to Planetic Web?
        <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:underline">Create an account</a>
    </p>
@endsection
