<?php

namespace App\Console\Commands;

use App\Services\Registrar\PorkbunRegistrar;
use App\Services\Registrar\RegistrarInterface;
use App\Support\DomainName;
use Illuminate\Console\Command;
use Throwable;

/**
 * Safe, read-only health check for the active domain registrar: confirms
 * credentials, an availability lookup, and pricing — plus an optional Porkbun
 * dry-run registration that charges nothing. Secrets are never printed.
 *
 *   php artisan registrar:test
 *   php artisan registrar:test example.com
 *   php artisan registrar:test some-unregistered-name.com --register
 */
class TestRegistrarConnectionCommand extends Command
{
    protected $signature = 'registrar:test {domain? : Domain to check availability/pricing for} {--register : Also run a no-charge Porkbun dry-run registration}';

    protected $description = 'Verify the active domain registrar (credentials, availability, pricing, optional dry-run registration).';

    public function handle(): int
    {
        $name = config('domain.default_registrar');
        $this->info("Active registrar: {$name}");
        $this->line('Enabled providers: '.collect(['porkbun', 'namesilo', 'namecheap'])
            ->map(fn ($p) => $p.'='.(config("domain.{$p}.enabled") ? 'yes' : 'no'))
            ->implode('   '));

        try {
            $registrar = app(RegistrarInterface::class);
        } catch (Throwable $e) {
            $this->error('Could not resolve the registrar: '.$e->getMessage());

            return self::FAILURE;
        }

        // 1. Connection / credentials (Porkbun exposes a dedicated ping).
        if ($registrar instanceof PorkbunRegistrar) {
            try {
                $ping = $registrar->ping();
                $this->info('✓ Connection OK (Porkbun /ping). Caller IP: '.($ping['yourIp'] ?? $ping['ip'] ?? 'n/a'));
            } catch (Throwable $e) {
                $this->error('✗ Connection/credentials failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        $domain = $this->argument('domain');
        if (! $domain) {
            $this->comment('Tip: pass a domain to test availability & pricing, e.g. php artisan registrar:test example.com');

            return self::SUCCESS;
        }
        $domain = DomainName::normalise($domain);

        // 2. Availability.
        try {
            $a = $registrar->checkAvailability($domain);
            $this->info('✓ Availability: '.$domain.' → '.($a['available'] ? 'AVAILABLE' : 'taken')
                .($a['price'] ? ' (registrar price '.$a['price'].' '.$a['currency'].')' : ''));
        } catch (Throwable $e) {
            $this->error('✗ Availability check failed: '.$e->getMessage());

            return self::FAILURE;
        }

        // 3. Pricing.
        try {
            $p = $registrar->getPricing($domain);
            $p['supported']
                ? $this->info('✓ Pricing .'.$p['tld'].': registration '.$p['registration'].' / renewal '.$p['renewal'].' '.$p['currency'])
                : $this->comment('• Pricing API not supported by this registrar.');
        } catch (Throwable $e) {
            $this->warn('Pricing lookup failed: '.$e->getMessage());
        }

        // 4. Optional dry-run registration — Porkbun only, never charges.
        if ($this->option('register')) {
            if (! $registrar instanceof PorkbunRegistrar) {
                $this->warn('--register dry-run is only supported for Porkbun.');

                return self::SUCCESS;
            }

            try {
                $r = $registrar->registerDomain(['domain' => $domain, 'whois_privacy' => true, 'dry_run' => true]);
                $this->info('✓ Dry-run registration OK (no charge). Order reference: '.($r['registrar_order_id'] ?? 'n/a'));
            } catch (Throwable $e) {
                $this->error('✗ Dry-run registration failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
