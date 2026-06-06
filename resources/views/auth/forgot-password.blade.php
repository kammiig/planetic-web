@extends('layouts.auth')

@section('title', 'Forgot password')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Forgot your password?</h1>
    <p class="mt-1 text-sm text-slate-500">Enter your email address and we'll send you a secure reset link.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4" novalidate>
        @csrf
        <x-field name="email" label="Email address" type="email" autocomplete="email" :required="true" autofocus />
        <button type="submit" class="btn-primary w-full">Email password reset link</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Back to sign in</a>
    </p>
@endsection
