<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Small value helper for parsing and validating domain names before they are
 * sent to a registrar (Security & Access §14: always validate domain names).
 */
class DomainName
{
    public function __construct(
        public readonly string $sld,
        public readonly string $tld,
    ) {}

    public static function parse(string $domain): self
    {
        $domain = self::normalise($domain);
        $sld = Str::before($domain, '.');
        $tld = Str::after($domain, '.');

        return new self($sld, $tld);
    }

    public function full(): string
    {
        return $this->sld.'.'.$this->tld;
    }

    public static function normalise(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim((string) $domain, '/');

        return trim((string) $domain);
    }

    /**
     * A registrable "sld.tld" or "sld.co.uk" style name. Rejects bare labels,
     * spaces, protocols and obviously invalid characters.
     */
    public static function isValid(string $domain): bool
    {
        $domain = self::normalise($domain);

        if (! str_contains($domain, '.') || str_contains($domain, ' ')) {
            return false;
        }

        return (bool) preg_match(
            '/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/',
            $domain,
        );
    }
}
