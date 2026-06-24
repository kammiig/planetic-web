<?php

namespace App\Providers;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\NamecheapRegistrar;
use App\Services\Registrar\NameSiloRegistrar;
use App\Services\Registrar\PorkbunRegistrar;
use App\Services\Registrar\RegistrarInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the swappable third-party integration contracts. The registrar
 * implementation is chosen purely from config (DEFAULT_REGISTRAR), so the
 * platform is never locked into one provider. Porkbun is the default;
 * NameSilo and Namecheap are optional fallbacks. A registrar that is
 * explicitly disabled (X_ENABLED=false) but still selected as the default
 * is rejected with a clear, actionable error rather than failing obscurely.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegistrarInterface::class, function ($app) {
            $name = config('domain.default_registrar', 'porkbun');

            if (config("domain.{$name}.enabled") === false) {
                throw new RegistrarException(
                    "The selected domain registrar '{$name}' is disabled. Set "
                    .strtoupper($name)."_ENABLED=true in the server .env, or change DEFAULT_REGISTRAR.",
                    registrar: $name,
                );
            }

            return match ($name) {
                'namesilo' => $app->make(NameSiloRegistrar::class),
                'namecheap' => $app->make(NamecheapRegistrar::class),
                default => $app->make(PorkbunRegistrar::class),
            };
        });
    }
}
