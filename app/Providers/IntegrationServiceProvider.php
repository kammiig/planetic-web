<?php

namespace App\Providers;

use App\Exceptions\RegistrarException;
use App\Models\SiteSetting;
use App\Services\Registrar\CloudflareRegistrar;
use App\Services\Registrar\FallbackRegistrar;
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
 * locked into one provider. Cloudflare is the default, wrapped in a
 * FallbackRegistrar that routes TLDs outside Cloudflare's Registrar API beta
 * (notably .uk/.co.uk) to Porkbun; NameSilo and Namecheap are alternatives.
 * A registrar that is explicitly disabled (X_ENABLED=false) but still selected
 * is rejected with a clear, actionable error. API secrets stay in the server
 * environment and are never read here.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public const REGISTRARS = ['cloudflare', 'porkbun', 'namesilo', 'namecheap'];

    public function register(): void
    {
        $this->app->bind(RegistrarInterface::class, function ($app) {
            $name = $this->resolveRegistrarName();

            $this->assertEnabled($name);

            $primary = $this->makeRegistrar($app, $name);
            $fallback = $this->resolveFallback($app, $name);

            return $fallback ? new FallbackRegistrar($primary, $fallback) : $primary;
        });
    }

    private function makeRegistrar($app, string $name): RegistrarInterface
    {
        return match ($name) {
            'porkbun' => $app->make(PorkbunRegistrar::class),
            'namesilo' => $app->make(NameSiloRegistrar::class),
            'namecheap' => $app->make(NamecheapRegistrar::class),
            default => $app->make(CloudflareRegistrar::class),
        };
    }

    /**
     * The fallback is only wired when it is a different, enabled registrar.
     * A misconfigured or disabled fallback is silently skipped rather than
     * breaking the primary — the primary alone is still a working platform.
     */
    private function resolveFallback($app, string $primaryName): ?RegistrarInterface
    {
        $fallback = (string) config('domain.fallback_registrar', 'porkbun');

        if ($fallback === $primaryName || ! in_array($fallback, self::REGISTRARS, true)) {
            return null;
        }

        if (config("domain.{$fallback}.enabled") === false) {
            return null;
        }

        return $this->makeRegistrar($app, $fallback);
    }

    private function assertEnabled(string $name): void
    {
        if (config("domain.{$name}.enabled") !== false) {
            return;
        }

        // The Cloudflare toggle is CLOUDFLARE_REGISTRAR_ENABLED, not
        // CLOUDFLARE_ENABLED — name the real variable so the fix is obvious.
        $variable = $name === 'cloudflare' ? 'CLOUDFLARE_REGISTRAR_ENABLED' : strtoupper($name).'_ENABLED';

        throw new RegistrarException(
            "The selected domain registrar '{$name}' is disabled. Set {$variable}=true in the server .env, "
            .'or change the default registrar.',
            registrar: $name,
        );
    }

    /**
     * Admin override (site_settings: registrar.default) takes precedence over
     * the DEFAULT_REGISTRAR config. Reading the setting is wrapped so that a
     * missing settings table (e.g. before migrations) never breaks resolution.
     */
    private function resolveRegistrarName(): string
    {
        $config = config('domain.default_registrar', 'cloudflare');

        try {
            $override = SiteSetting::get('registrar.default');
        } catch (Throwable) {
            $override = null;
        }

        return in_array($override, self::REGISTRARS, true) ? $override : $config;
    }
}
