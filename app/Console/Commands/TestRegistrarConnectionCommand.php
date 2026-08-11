<?php

namespace App\Console\Commands;

use App\Exceptions\RegistrarException;
use App\Services\Registrar\CloudflareRegistrar;
use App\Services\Registrar\PorkbunRegistrar;
use App\Services\Registrar\RegistrarInterface;
use App\Services\Registrar\RegistrarResolver;
use App\Support\DomainName;
use Illuminate\Console\Command;
use Throwable;

/**
 * Safe, read-only health check for the active domain registrar: confirms
 * credentials, an availability lookup, and pricing — plus an optional no-charge
 * dry-run registration. Secrets are never printed.
 *
 *   php artisan registrar:test
 *   php artisan registrar:test example.com
 *   php artisan registrar:test some-unregistered-name.com --register
 *   php artisan registrar:test --tld-support=co.uk,com,org
 *
 * --tld-support answers the question the Cloudflare beta forces: which of our
 * TLDs can Cloudflare's Registrar API actually register today, and which will
 * route to the fallback registrar? Run it before switching a live deployment.
 */
class TestRegistrarConnectionCommand extends Command
{
    protected $signature = 'registrar:test
        {domain? : Domain to check availability/pricing for}
        {--register : Also run a no-charge dry-run registration}
        {--tld-support= : Comma-separated TLDs to probe for Cloudflare API support (default: the configured suggestion TLDs)}';

    protected $description = 'Verify the active domain registrar (credentials, availability, pricing, TLD support, optional dry-run registration).';

    public function handle(RegistrarResolver $resolver): int
    {
        $name = config('domain.default_registrar');
        $fallback = (string) config('domain.fallback_registrar', 'none');

        $this->info("Active registrar: {$name}");
        $this->line('Fallback registrar: '.($fallback !== '' ? $fallback : 'none'));
        $this->line('Enabled providers: '.collect(['cloudflare', 'porkbun', 'namesilo', 'namecheap'])
            ->map(fn ($p) => $p.'='.(config("domain.{$p}.enabled") ? 'yes' : 'no'))
            ->implode('   '));

        try {
            $registrar = app(RegistrarInterface::class);
        } catch (Throwable $e) {
            $this->error('Could not resolve the registrar: '.$e->getMessage());

            return self::FAILURE;
        }

        // 1. Connection / credentials against the concrete primary provider
        //    (the resolved instance may be a FallbackRegistrar wrapper).
        $primary = $resolver->named(is_string($name) ? $name : null);

        if (! $this->verifyConnection($primary)) {
            return self::FAILURE;
        }

        if ($this->option('tld-support') !== null) {
            return $this->probeTldSupport($resolver);
        }

        $domain = $this->argument('domain');
        if (! $domain) {
            $this->comment('Tip: pass a domain to test availability & pricing, e.g. php artisan registrar:test example.com');
            $this->comment('Tip: run --tld-support to see which TLDs the Cloudflare API beta can register.');

            return self::SUCCESS;
        }
        $domain = DomainName::normalise($domain);

        // 2. Availability (through the fallback-aware registrar, so this shows
        //    what a real order would experience).
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
                : $this->comment('• Pricing API not supported by this registrar for .'.$p['tld'].'.');
        } catch (Throwable $e) {
            $this->warn('Pricing lookup failed: '.$e->getMessage());
        }

        // 4. Optional dry-run registration — never charges.
        if ($this->option('register')) {
            try {
                $r = $registrar->registerDomain(['domain' => $domain, 'whois_privacy' => true, 'dry_run' => true]);
                $this->info('✓ Dry-run registration OK (no charge)'
                    .' via '.($r['registrar'] ?? 'primary')
                    .'. Order reference: '.($r['registrar_order_id'] ?? 'n/a'));
            } catch (Throwable $e) {
                $this->error('✗ Dry-run registration failed: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /** Provider-specific credential check; providers without a ping are skipped. */
    private function verifyConnection(RegistrarInterface $primary): bool
    {
        try {
            if ($primary instanceof CloudflareRegistrar) {
                $ping = $primary->ping();
                $this->info('✓ Connection OK (Cloudflare Registrar). Account: '.($ping['account_id'] ?? 'n/a'));
            } elseif ($primary instanceof PorkbunRegistrar) {
                $ping = $primary->ping();
                $this->info('✓ Connection OK (Porkbun /ping). Caller IP: '.($ping['yourIp'] ?? $ping['ip'] ?? 'n/a'));
            }
        } catch (Throwable $e) {
            $this->error('✗ Connection/credentials failed: '.$e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Probe each TLD through Cloudflare's domain-check using a random SLD, and
     * report whether the API beta will accept it or whether it routes to the
     * fallback registrar.
     */
    private function probeTldSupport(RegistrarResolver $resolver): int
    {
        $option = trim((string) $this->option('tld-support'));

        $tlds = $option !== ''
            ? array_map(fn ($t) => ltrim(trim($t), '.'), explode(',', $option))
            : (array) config('domain.suggestion_tlds', []);

        $tlds = array_values(array_filter($tlds));

        if ($tlds === []) {
            $this->warn('No TLDs to probe.');

            return self::SUCCESS;
        }

        $cloudflare = $resolver->named('cloudflare');
        $fallback = (string) config('domain.fallback_registrar', 'none');
        $rows = [];
        $unsupported = 0;

        $this->newLine();
        $this->info('Probing Cloudflare Registrar API support (no domains are registered):');

        foreach ($tlds as $tld) {
            $probe = 'pw-support-check-'.bin2hex(random_bytes(4)).'.'.$tld;

            try {
                $result = $cloudflare->checkAvailability($probe);
                $rows[] = ['.'.$tld, '<info>supported</info>', ($result['price'] ?? '?').' '.$result['currency'], 'cloudflare'];
            } catch (RegistrarException $e) {
                if ($e->fallbackEligible) {
                    $unsupported++;
                    $rows[] = ['.'.$tld, '<comment>not in API beta</comment>', '—', $fallback];

                    continue;
                }

                $rows[] = ['.'.$tld, '<error>error</error>', mb_strimwidth($e->getMessage(), 0, 60, '…'), '—'];
            } catch (Throwable $e) {
                $rows[] = ['.'.$tld, '<error>error</error>', mb_strimwidth($e->getMessage(), 0, 60, '…'), '—'];
            }
        }

        $this->table(['TLD', 'Cloudflare API', 'Price / detail', 'Registers via'], $rows);

        if ($unsupported > 0) {
            $fallbackOk = in_array($fallback, ['porkbun', 'namesilo', 'namecheap'], true)
                && config("domain.{$fallback}.enabled") !== false;

            $fallbackOk
                ? $this->info("{$unsupported} TLD(s) fall outside the Cloudflare API beta and will register via {$fallback}.")
                : $this->error("{$unsupported} TLD(s) fall outside the Cloudflare API beta and NO usable fallback registrar is configured — those orders would FAIL. Set FALLBACK_REGISTRAR and its credentials.");

            return $fallbackOk ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }
}
