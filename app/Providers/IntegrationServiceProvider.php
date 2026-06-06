<?php

namespace App\Providers;

use App\Services\Registrar\NamecheapRegistrar;
use App\Services\Registrar\NameSiloRegistrar;
use App\Services\Registrar\RegistrarInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the swappable third-party integration contracts. The registrar
 * implementation is chosen purely from config (DOMAIN_REGISTRAR), so the
 * platform is never locked into one provider.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegistrarInterface::class, function ($app) {
            return match (config('domain.default_registrar')) {
                'namecheap' => $app->make(NamecheapRegistrar::class),
                default => $app->make(NameSiloRegistrar::class),
            };
        });
    }
}
