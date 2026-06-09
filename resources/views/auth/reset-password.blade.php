@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Choose a new password</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-field name="email" label="Email address" type="email" autocomplete="email"
                 :value="old('email', $request->email)" :required="true" />
        <x-field name="password" label="New password" type="password" autocomplete="new-password" :required="true"
                 help="At least 10 characters with upper & lower case, a number and a symbol." />
        <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" :required="true" />

        <button type="submit" class="btn-primary w-full">Reset password</button>
    </form>
@endsection
