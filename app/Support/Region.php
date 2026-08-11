<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * A regional storefront: its URL prefix, currency, tax treatment and hreflang.
 *
 * A request's region comes from its URL prefix and nothing else. Geolocation
 * never changes what a URL returns — see config/regions.php for why that is
 * load-bearing for both Cloudflare caching and Google indexing.
 *
 * The active region for the current request is resolved once by
 * App\Http\Middleware\ResolveRegion and read back through Region::current().
 */
class Region
{
    private static ?self $current = null;

    /**
     * @param  array<string, mixed>  $config
     */
    private function __construct(
        public readonly string $key,
        private readonly array $config,
    ) {}

    /* ------------------------------------------------------------------ */
    /* Construction */
    /* ------------------------------------------------------------------ */

    public static function make(string $key): self
    {
        $config = config("regions.regions.{$key}");

        if (! is_array($config)) {
            $key = self::defaultKey();
            $config = (array) config("regions.regions.{$key}", []);
        }

        return new self($key, $config);
    }

    public static function defaultKey(): string
    {
        $key = (string) config('regions.default', 'uk');

        return self::exists($key) ? $key : (string) array_key_first((array) config('regions.regions', []));
    }

    public static function default(): self
    {
        return self::make(self::defaultKey());
    }

    public static function exists(string $key): bool
    {
        return is_array(config("regions.regions.{$key}"));
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys((array) config('regions.regions', []));
    }

    /** @return array<int, self> */
    public static function all(): array
    {
        return array_map(fn (string $key) => self::make($key), self::keys());
    }

    /* ------------------------------------------------------------------ */
    /* Current request */
    /* ------------------------------------------------------------------ */

    public static function current(): self
    {
        return self::$current ??= self::default();
    }

    public static function setCurrent(self $region): void
    {
        self::$current = $region;
    }

    /** Testing/teardown hook — the resolved region must not leak between requests. */
    public static function flush(): void
    {
        self::$current = null;
    }

    /* ------------------------------------------------------------------ */
    /* Lookups */
    /* ------------------------------------------------------------------ */

    /**
     * The region serving a country code, or null when detection yields nothing
     * usable. Cloudflare sends "XX" for unknown and "T1" for Tor exit nodes;
     * neither should silently become the catch-all region.
     */
    public static function forCountry(?string $countryCode): ?self
    {
        $code = strtoupper(trim((string) $countryCode));

        if ($code === '' || in_array($code, ['XX', 'T1'], true)) {
            return null;
        }

        foreach (self::keys() as $key) {
            $countries = (array) config("regions.regions.{$key}.countries", []);

            if (in_array($code, array_map('strtoupper', $countries), true)) {
                return self::make($key);
            }
        }

        return self::catchAll();
    }

    /** The region that takes every country not explicitly claimed. */
    public static function catchAll(): self
    {
        foreach (self::keys() as $key) {
            if (config("regions.regions.{$key}.catch_all") === true) {
                return self::make($key);
            }
        }

        return self::default();
    }

    /** The region owning a URL prefix segment ("" → the root/default tree). */
    public static function forPrefix(?string $prefix): self
    {
        $prefix = trim((string) $prefix, '/');

        foreach (self::keys() as $key) {
            if (trim((string) config("regions.regions.{$key}.prefix", ''), '/') === $prefix && $prefix !== '') {
                return self::make($key);
            }
        }

        return self::default();
    }

    /* ------------------------------------------------------------------ */
    /* Attributes */
    /* ------------------------------------------------------------------ */

    public function name(): string
    {
        return (string) ($this->config['name'] ?? 'United Kingdom');
    }

    public function shortName(): string
    {
        return (string) ($this->config['short'] ?? $this->name());
    }

    public function flag(): string
    {
        return (string) ($this->config['flag'] ?? '');
    }

    /** URL prefix segment without slashes; empty string for the root tree. */
    public function prefix(): string
    {
        return trim((string) ($this->config['prefix'] ?? ''), '/');
    }

    public function isDefault(): bool
    {
        return $this->key === self::defaultKey();
    }

    /** ISO-4217 code, uppercase (GBP). */
    public function currency(): string
    {
        return strtoupper((string) ($this->config['currency'] ?? 'GBP'));
    }

    /** Lowercase currency code, as Stripe expects it. */
    public function stripeCurrency(): string
    {
        return strtolower($this->currency());
    }

    public function symbol(): string
    {
        return (string) ($this->config['symbol'] ?? '£');
    }

    public function hreflang(): string
    {
        return (string) ($this->config['hreflang'] ?? 'en');
    }

    public function locale(): string
    {
        return (string) ($this->config['locale'] ?? 'en_GB');
    }

    public function defaultTld(): string
    {
        return (string) ($this->config['default_tld'] ?? 'co.uk');
    }

    /* ------------------------------------------------------------------ */
    /* Tax */
    /* ------------------------------------------------------------------ */

    /**
     * Whether a tax line should be shown at all. False until the business is
     * actually registered — a "VAT £0.00" row implies a registration that does
     * not exist, so the row is omitted entirely rather than shown as zero.
     */
    public function chargesTax(): bool
    {
        return (bool) ($this->config['tax']['registered'] ?? false)
            && (float) ($this->config['tax']['rate'] ?? 0) > 0;
    }

    public function taxRate(): float
    {
        return $this->chargesTax() ? (float) ($this->config['tax']['rate'] ?? 0) : 0.0;
    }

    public function taxLabel(): string
    {
        return (string) ($this->config['tax']['label'] ?? 'VAT');
    }

    public function taxNumber(): ?string
    {
        $number = $this->config['tax']['number'] ?? null;

        return filled($number) ? (string) $number : null;
    }

    /** Whether displayed prices already include tax (UK consumer pricing does). */
    public function taxInclusive(): bool
    {
        return (bool) ($this->config['tax']['inclusive'] ?? true);
    }

    /**
     * The tax contained within a tax-inclusive total, or added on top of a
     * tax-exclusive one. Returns 0.0 when the region charges no tax.
     */
    public function taxOn(float $amount): float
    {
        $rate = $this->taxRate();

        if ($rate <= 0) {
            return 0.0;
        }

        return $this->taxInclusive()
            ? round($amount - ($amount / (1 + $rate)), 2)
            : round($amount * $rate, 2);
    }

    /* ------------------------------------------------------------------ */
    /* URLs */
    /* ------------------------------------------------------------------ */

    /**
     * A named route in THIS region. Regional routes are registered with a
     * "{key}." name prefix (e.g. "int.hosting.index"); the default region keeps
     * the bare names so every existing route() call and every published link
     * keeps working untouched.
     */
    public function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $candidate = $this->isDefault() ? $name : $this->key.'.'.$name;

        if (! Route::has($candidate)) {
            // Routes outside the regional trees (checkout, dashboard, auth)
            // exist once only — fall back to the shared name.
            $candidate = $name;
        }

        return route($candidate, $parameters, $absolute);
    }

    /**
     * Translate a path from any region's tree into this region's tree, so the
     * selector can move a visitor between storefronts without losing the page
     * they were on.
     */
    public function translatePath(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        // Strip any existing region prefix.
        foreach (self::all() as $region) {
            $prefix = $region->prefix();

            if ($prefix === '') {
                continue;
            }

            if ($path === '/'.$prefix || str_starts_with($path, '/'.$prefix.'/')) {
                $path = substr($path, strlen($prefix) + 1);
                $path = $path === '' ? '/' : $path;
                break;
            }
        }

        $prefix = $this->prefix();

        if ($prefix === '') {
            return $path === '' ? '/' : $path;
        }

        return rtrim('/'.$prefix.rtrim($path, '/'), '/') ?: '/'.$prefix;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name(),
            'short' => $this->shortName(),
            'flag' => $this->flag(),
            'prefix' => $this->prefix(),
            'currency' => $this->currency(),
            'symbol' => $this->symbol(),
            'hreflang' => $this->hreflang(),
        ];
    }
}
