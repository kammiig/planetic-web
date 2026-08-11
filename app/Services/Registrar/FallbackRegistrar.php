<?php

namespace App\Services\Registrar;

use App\Exceptions\RegistrarException;
use Illuminate\Support\Facades\Log;

/**
 * Routes registrar calls to a primary provider and, when that provider cannot
 * service the request *at all*, retries them on a secondary.
 *
 * This exists for one concrete reason: the Cloudflare Registrar API is in beta
 * and only covers a subset of the extensions Cloudflare supports in its
 * dashboard — .uk is Cloudflare's own documented example of an extension that
 * answers {registrable:false, reason:"extension_not_supported_via_api"}. For a
 * UK business whose primary TLD is .co.uk, that gap cannot be allowed to fail a
 * paid order, so those domains route to Porkbun automatically.
 *
 * Only failures explicitly marked fallbackEligible are retried. A domain that is
 * genuinely taken, a rejected contact, a declined card or a bad token all fail
 * fast on the primary — the fallback is never a way to paper over a real error,
 * and a second registrar is never charged for a domain the first one may already
 * have bought.
 */
class FallbackRegistrar implements RegistrarInterface
{
    public function __construct(
        private readonly RegistrarInterface $primary,
        private readonly ?RegistrarInterface $fallback = null,
    ) {}

    /**
     * The configured primary. Per-registration attribution comes back in the
     * `registrar` key of registerDomain()'s result, which is what gets persisted
     * against the domain — so a fallback registration is recorded honestly.
     */
    public function name(): string
    {
        return $this->primary->name();
    }

    public function checkAvailability(string $domain): array
    {
        return $this->route(__FUNCTION__, "availability check for {$domain}", fn (RegistrarInterface $r) => $r->checkAvailability($domain));
    }

    public function getPricing(string $tld): array
    {
        return $this->route(__FUNCTION__, "pricing for {$tld}", fn (RegistrarInterface $r) => $r->getPricing($tld));
    }

    public function registerDomain(array $data): array
    {
        $domain = $data['domain'] ?? 'domain';

        return $this->route(__FUNCTION__, "registration of {$domain}", function (RegistrarInterface $r) use ($data) {
            $result = $r->registerDomain($data);

            // Attribute the registration to whoever actually performed it, so
            // the domain record and later API calls target the right provider.
            $result['registrar'] ??= $r->name();

            return $result;
        });
    }

    public function renewDomain(string $domain, int $years = 1): array
    {
        return $this->route(__FUNCTION__, "renewal of {$domain}", fn (RegistrarInterface $r) => $r->renewDomain($domain, $years));
    }

    public function getDomainInfo(string $domain): array
    {
        return $this->route(__FUNCTION__, "domain info for {$domain}", fn (RegistrarInterface $r) => $r->getDomainInfo($domain));
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        return $this->route(__FUNCTION__, "nameserver update for {$domain}", fn (RegistrarInterface $r) => $r->updateNameservers($domain, $nameservers));
    }

    /**
     * Run an operation on the primary, retrying on the fallback only when the
     * primary reported that it cannot service the request.
     *
     * @template TReturn of array<string, mixed>
     *
     * @param  callable(RegistrarInterface): TReturn  $operation
     * @return TReturn
     */
    private function route(string $method, string $label, callable $operation): array
    {
        try {
            return $operation($this->primary);
        } catch (RegistrarException $e) {
            if (! $e->fallbackEligible || $this->fallback === null) {
                throw $e;
            }

            Log::channel('stack')->info('Registrar fallback engaged.', [
                'operation' => $method,
                'label' => $label,
                'primary' => $this->primary->name(),
                'fallback' => $this->fallback->name(),
                'reason' => $e->getMessage(),
            ]);

            return $operation($this->fallback);
        }
    }
}
