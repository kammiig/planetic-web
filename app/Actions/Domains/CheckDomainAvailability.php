<?php

namespace App\Actions\Domains;

use App\Enums\ProductType;
use App\Exceptions\RegistrarException;
use App\Models\Product;
use App\Models\TldPricing;
use App\Services\Registrar\RegistrarInterface;
use App\Support\DomainName;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestrates a domain availability search: validates the name, asks the
 * configured registrar, prices it from our own GBP catalogue (never from the
 * registrar's raw, possibly-USD figure), and — when taken — offers a few
 * available alternatives on common TLDs.
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

        return [
            'success' => true,
            'domain' => $domain,
            'available' => $result['available'],
            'premium' => $result['premium'],
            'price' => $result['available'] ? $price : null,
            'currency' => 'GBP',
            // Kept for the homepage hero (only when the exact name is taken).
            'suggestions' => $result['available'] ? [] : $this->suggestions($domain),
            // A richer set of available alternative TLDs for the full search page.
            // Guarded so the lightweight hero search does not pay the extra lookups.
            'alternatives' => $withAlternatives ? $this->alternatives($domain) : [],
        ];
    }

    /**
     * Up to 8 available alternative-TLD variants for the "More options" list.
     * Bounded to keep registrar calls (and latency) in check; all results are
     * cached for 60s so repeat searches are instant.
     *
     * @return array<int, array{domain: string, available: bool, price: string}>
     */
    private function alternatives(string $domain): array
    {
        $parsed = DomainName::parse($domain);
        $out = [];
        $checked = 0;

        foreach ($this->suggestionTlds() as $tld) {
            if (count($out) >= 8 || $checked >= 10) {
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

            if (! empty($check['available'])) {
                $out[] = ['domain' => $candidate, 'available' => true, 'price' => $this->priceForDomain($candidate)];
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

            if (! empty($check['available'])) {
                $suggestions[] = ['domain' => $candidate, 'available' => true, 'price' => $this->priceForDomain($candidate)];
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
     * Customer-facing GBP price for a specific domain, resolved from the admin
     * TLD price book (longest-matching suffix). Falls back to the legacy flat
     * catalogue price, then to a hard default, so search never breaks.
     */
    private function priceForDomain(string $domain): string
    {
        $price = TldPricing::priceForDomain($domain);

        if ($price === null) {
            $price = Product::ofType(ProductType::Domain)->active()->first()?->priceFor('yearly')?->amount ?? 12.99;
        }

        return number_format((float) $price, 2, '.', '');
    }
}
