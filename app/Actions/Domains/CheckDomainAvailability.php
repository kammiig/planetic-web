<?php

namespace App\Actions\Domains;

use App\Enums\ProductType;
use App\Exceptions\RegistrarException;
use App\Models\Product;
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
     * @return array{success: bool, domain: string, available: bool, premium: bool, price: ?string, currency: string, suggestions: array<int, array{domain: string, available: bool, price: string}>}
     *
     * @throws RegistrarException
     */
    public function handle(string $domain): array
    {
        $domain = DomainName::normalise($domain);
        $result = $this->lookup($domain);
        $price = $this->displayPrice();

        return [
            'success' => true,
            'domain' => $domain,
            'available' => $result['available'],
            'premium' => $result['premium'],
            'price' => $result['available'] ? $price : null,
            'currency' => 'GBP',
            'suggestions' => $result['available'] ? [] : $this->suggestions($domain, $price),
        ];
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
    private function suggestions(string $domain, string $price): array
    {
        $parsed = DomainName::parse($domain);
        $suggestions = [];

        foreach (config('domain.suggestion_tlds', []) as $tld) {
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
                $suggestions[] = ['domain' => $candidate, 'available' => true, 'price' => $price];
            }
        }

        return $suggestions;
    }

    /** Standard GBP yearly domain price from our catalogue. */
    private function displayPrice(): string
    {
        $amount = Product::ofType(ProductType::Domain)->active()->first()?->priceFor('yearly')?->amount;

        return number_format((float) ($amount ?? 12.99), 2, '.', '');
    }
}
