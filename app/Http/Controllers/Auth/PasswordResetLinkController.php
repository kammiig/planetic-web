<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Always returns the same message regardless of whether the email
        // exists, so account existence is never disclosed.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that email address is registered, a password reset link has been sent.');
    }
}
