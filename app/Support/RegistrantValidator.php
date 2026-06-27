<?php

namespace App\Support;

/**
 * Validates registrant/contact data before a domain registration is attempted,
 * so an order with missing or placeholder contact details fails fast with a
 * clear, actionable reason instead of a cryptic registrar HTTP 400. Also flags
 * TLD-specific issues (e.g. UK postcode format for .co.uk / Nominet).
 */
class RegistrantValidator
{
    /** Values our fallback mapping uses when a field is actually empty. */
    private const PLACEHOLDERS = ['N/A', 'NA', '00000', '+44.0000000000', 'CUSTOMER', 'ACCOUNT'];

    private const REQUIRED = [
        'first_name' => 'first name',
        'last_name' => 'last name',
        'email' => 'email address',
        'phone' => 'phone number',
        'address_line_1' => 'address',
        'city' => 'city',
        'postcode' => 'postcode',
        'country' => 'country code',
    ];

    /**
     * All issues (missing fields + format problems). Empty array = valid.
     *
     * @param  array<string, mixed>  $contact
     * @return array<int, string>
     */
    public static function validate(array $contact, string $tld = ''): array
    {
        return [...self::missing($contact), ...self::formatIssues($contact, $tld)];
    }

    /**
     * Only the hard "missing/placeholder required field" problems — used to
     * block before an API call.
     *
     * @param  array<string, mixed>  $contact
     * @return array<int, string>
     */
    public static function missing(array $contact): array
    {
        $issues = [];

        foreach (self::REQUIRED as $key => $label) {
            $value = strtoupper(trim((string) ($contact[$key] ?? '')));

            if ($value === '' || in_array($value, self::PLACEHOLDERS, true)) {
                $issues[] = "Missing or placeholder {$label}";
            }
        }

        return $issues;
    }

    /**
     * Non-blocking format checks (invalid email, country code, phone, UK postcode).
     *
     * @param  array<string, mixed>  $contact
     * @return array<int, string>
     */
    public static function formatIssues(array $contact, string $tld = ''): array
    {
        $issues = [];

        if (filled($contact['email'] ?? null) && ! filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $issues[] = 'Email address is not valid';
        }

        $country = strtoupper(trim((string) ($contact['country'] ?? '')));
        if ($country !== '' && ! preg_match('/^[A-Z]{2}$/', $country)) {
            $issues[] = 'Country must be a 2-letter ISO code (e.g. GB)';
        }

        $digits = preg_replace('/\D/', '', (string) ($contact['phone'] ?? ''));
        if (filled($contact['phone'] ?? null) && strlen((string) $digits) < 7) {
            $issues[] = 'Phone number looks invalid (too short)';
        }

        $tld = strtolower(ltrim($tld, '.'));
        if (in_array($tld, ['co.uk', 'uk', 'org.uk', 'me.uk', 'ltd.uk', 'plc.uk'], true)) {
            $postcode = strtoupper(trim((string) ($contact['postcode'] ?? '')));
            if ($postcode !== '' && $country === 'GB'
                && ! preg_match('/^[A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}$/', $postcode)) {
                $issues[] = 'UK postcode format looks invalid (expected e.g. "BL9 0RT")';
            }
        }

        return $issues;
    }

    /** @param  array<string, mixed>  $contact */
    public static function isValid(array $contact): bool
    {
        return self::missing($contact) === [];
    }
}
