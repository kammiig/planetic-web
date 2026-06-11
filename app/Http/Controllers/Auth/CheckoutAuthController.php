<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Inline authentication for the checkout page. Both endpoints are called via
 * AJAX from the checkout itself, so the customer never leaves the page and the
 * cart (tracked by session 'cart_id', which survives session regeneration) is
 * preserved. The browser reloads /checkout afterwards and lands on the next
 * step, already signed in.
 *
 * Email verification is intentionally NOT required here: the verification
 * email goes out in the background (User::sendEmailVerificationNotification
 * never throws) and a dashboard banner nudges the customer later. A purchase
 * is never blocked on an unverified address.
 */
class CheckoutAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Already signed in (e.g. double submit or a second tab) — just continue.
        if (Auth::check()) {
            return response()->json(['ok' => true]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'You must accept the terms of use and renewal policy to continue.',
            'email.unique' => 'An account with this email already exists — switch to "Sign in" below to continue.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => 'active',
        ]);

        $user->assignRole(RoleName::Customer);

        // Sends the verification email in the background; never blocks checkout.
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    }

    public function login(Request $request): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(['ok' => true]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account is not active. Please contact support.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    }

    /** Same policy as the standalone login: 5 attempts per minute per email+IP. */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}
