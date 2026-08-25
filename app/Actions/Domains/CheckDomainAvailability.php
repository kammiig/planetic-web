<?php

namespace App\Actions\Domains;

use App\Enums\ProductType;
use App\Exceptions\RegistrarException;
use App\Models\Product;
use App\Models\TldPricing;
use App\Services\Registrar\RegistrarInterface;
use App\Support\DomainName;
use App\Support\Region;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestrates a domain availability search: validates the name, asks the
 * configured registrar, prices it from our own catalogue in the current
 * storefront's currency (never from the registrar's raw wholesale figure), and
 * — when taken — offers a few available alternatives on common TLDs.
 *
 * TLDs the storefront does not price in its own currency are omitted from
 * suggestions rather than quoted at a converted rate.
 */
class CheckDomainAvailability
{
    public function __construct(
        private readonly RegistrarInterface $registrar,
    ) {}

    /**
     * @return array{success: bool, domain: string, available: bool, premium: bool, price: ?string, currency: string, suggestions: array<int, array{domain: string, available: bool, price: string}>, alternatives: array<int, array{domain: string, available: bool, price: string}>}
     *
     * @throws RegistrarException
     */
    public function handle(string $domain, bool $withAlternatives = false): array
    {
        $domain = DomainName::normalise($domain);
        $result = $this->lookup($domain);
        $price = $this->priceForDomain($domain);
        $region = Region::current();

        return [
            'success' => true,
            'domain' => $domain,
            'available' => $result['available'],
            'premium' => $result['premium'],
            'price' => $result['available'] ? $price : null,
            'currency' => $region->currency(),
            'symbol' => $region->symbol(),
            // Porkbun's checkDomain is rate-limited to ~1 request / 10s, so we
            // keep the compact hero search to a SINGLE lookup whenever we can:
            // its suggestions only fire when the searched name is TAKEN. The
            // full search page always lists alternatives (that is its "More
            // options" section), bounded by config('domain.alternatives'). The
            // two never run together — hero → suggestions, page → alternatives.
            'suggestions' => (! $withAlternatives && ! $result['available']) ? $this->suggestions($domain) : [],
            // The full search page always offers other extensions, whether or
            // not the searched name was free — a customer who can have
            // example.com often still wants .co.uk and .net alongside it.
            'alternatives' => $withAlternatives ? $this->alternatives($domain) : [],
        ];
    }

    /**
     * Available alternative-TLD variants for the page's "More options" list.
     *
     * Bounded by config('domain.alternatives') to keep registrar calls (and
     * latency) in check: a burst that outruns a registrar's rate limit mostly
     * gets rejected anyway and wastes the budget for the next search. Every
     * lookup is cached for 60s, so repeat searches are instant and a customer
     * changing the sort order costs nothing.
     *
     * @return array<int, array{domain: string, available: bool, price: string}>
     */
    private function alternatives(string $domain): array
    {
        $parsed = DomainName::parse($domain);
        $limit = max(0, (int) config('domain.alternatives.limit', 6));
        $maxChecks = max($limit, (int) config('domain.alternatives.max_checks', 10));
        $out = [];
        $checked = 0;

        foreach ($this->suggestionTlds() as $tld) {
            if (count($out) >= $limit || $checked >= $maxChecks) {
                break;
            }
            if ($tld === $parsed->tld) {
                continue;
            }

            $candidate = $parsed->sld.'.'.$tld;
            $checked++;

            try {
                $check = Cache::remember(
                    'domain-availability:'.$candidate,
                    now()->addSeconds(60),
                    fn () => $this->registrar->checkAvailability($candidate),
                );
            } catch (RegistrarException) {
                continue;
            }

            if (! empty($check['available']) && ($price = $this->priceForDomain($candidate)) !== null) {
                $out[] = ['domain' => $candidate, 'available' => true, 'price' => $price];
            }
        }

        return $out;
    }

    /**
     * @return array{available: bool, premium: bool}
     */
    private function lookup(string $domain): array
    {
        // Short cache to avoid hammering the registrar on rapid retries.
        $cached = Cache::remember(
            'domain-availability:'.$domain,
            now()->addSeconds(60),
            fn () => $this->registrar->checkAvailability($domain),
        );

        return ['available' => (bool) $cached['available'], 'premium' => (bool) $cached['premium']];
    }

    /**
     * @return array<int, array{domain: string, available: bool, price: string}>
     */
    private function suggestions(string $domain): array
    {
        $parsed = DomainName::parse($domain);
        $suggestions = [];

        foreach ($this->suggestionTlds() as $tld) {
            if (count($suggestions) >= 3) {
                break;
            }
            if ($tld === $parsed->tld) {
                continue;
            }

            $candidate = $parsed->sld.'.'.$tld;

            try {
                $check = Cache::remember(
                    'domain-availability:'.$candidate,
                    now()->addSeconds(60),
                    fn () => $this->registrar->checkAvailability($candidate),
                );
            } catch (RegistrarException) {
                continue; // skip alternatives we cannot verify
            }

            if (! empty($check['available']) && ($price = $this->priceForDomain($candidate)) !== null) {
                $suggestions[] = ['domain' => $candidate, 'available' => true, 'price' => $price];
            }
        }

        return $suggestions;
    }

    /**
     * Alternative TLDs to suggest. Prefers the active, admin-managed TLD price
     * book (so suggestions and pricing stay in sync), falling back to config.
     *
     * @return array<int, string>
     */
    private function suggestionTlds(): array
    {
        $fromBook = TldPricing::activeMap()->keys()->all();

        return $fromBook ?: config('domain.suggestion_tlds', []);
    }

    /**
     * Customer-facing price for a domain in the current storefront's currency,
     * resolved from the admin TLD price book (longest-matching suffix), then the
     * catalogue product price. Returns null when the storefront publishes no
     * price for the extension — the caller drops it rather than inventing one by
     * conversion.
     */
    private function priceForDomain(string $domain): ?string
    {
        $currency = Region::current()->currency();
        $price = TldPricing::priceForDomain($domain, $currency);

        if ($price === null) {
            $price = Product::ofType(ProductType::Domain)->active()->first()?->priceFor('yearly', $currency)?->amount;
        }

        // Only the GBP storefront has a hardcoded last-resort figure; quoting it
        // under another symbol would undercharge by the exchange rate.
        if ($price === null && strtoupper($currency) === 'GBP') {
            $price = 12.99;
        }

        return $price === null ? null : number_format((float) $price, 2, '.', '');
    }
}
