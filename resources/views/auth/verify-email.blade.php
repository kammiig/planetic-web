@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Verify your email address</h1>
    <p class="mt-3 text-sm text-slate-600">
        Thanks for signing up! Before you can access your dashboard, please confirm your email address
        by clicking the link we just sent you. If you didn't receive it, we'll gladly send another.
    </p>

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary w-full">Sign out</button>
        </form>
    </div>
@endsection
