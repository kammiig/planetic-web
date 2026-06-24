<?php

namespace App\Providers;

use App\Exceptions\RegistrarException;
use App\Models\SiteSetting;
use App\Services\Registrar\NamecheapRegistrar;
use App\Services\Registrar\NameSiloRegistrar;
use App\Services\Registrar\PorkbunRegistrar;
use App\Services\Registrar\RegistrarInterface;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Binds the swappable third-party integration contracts. The registrar
 * implementation is chosen from the admin "Registrar Settings" override when
 * present, otherwise from config (DEFAULT_REGISTRAR), so the platform is never
 * locked into one provider. Porkbun is the default; NameSilo and Namecheap are
 * optional fallbacks. A registrar that is explicitly disabled (X_ENABLED=false)
 * but still selected is rejected with a clear, actionable error. API secrets
 * stay in the server environment and are never read here.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public const REGISTRARS = ['porkbun', 'namesilo', 'namecheap'];

    public function register(): void
    {
        $this->app->bind(RegistrarInterface::class, function ($app) {
            $name = $this->resolveRegistrarName();

            if (config("domain.{$name}.enabled") === false) {
                throw new RegistrarException(
                    "The selected domain registrar '{$name}' is disabled. Set "
                    .strtoupper($name)."_ENABLED=true in the server .env, or change the default registrar.",
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

    /**
     * Admin override (site_settings: registrar.default) takes precedence over
     * the DEFAULT_REGISTRAR config. Reading the setting is wrapped so that a
     * missing settings table (e.g. before migrations) never breaks resolution.
     */
    private function resolveRegistrarName(): string
    {
        $config = config('domain.default_registrar', 'porkbun');

        try {
            $override = SiteSetting::get('registrar.default');
        } catch (Throwable) {
            $override = null;
        }

        return in_array($override, self::REGISTRARS, true) ? $override : $config;
    }
}
