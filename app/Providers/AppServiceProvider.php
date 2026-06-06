<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Strong password policy (Security & Access §3.4): at least 10 chars
        // with upper + lower + number + symbol. These rules already reject the
        // documented weak passwords (password123, qwerty123, etc.).
        Password::defaults(function () {
            return Password::min(10)->mixedCase()->numbers()->symbols();
        });

        // Always emit HTTPS URLs in production (cookies are secure-only too).
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
