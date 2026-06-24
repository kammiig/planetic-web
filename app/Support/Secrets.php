<?php

namespace App\Support;

/**
 * Masks secrets in free-text strings before they are logged, emailed or shown
 * in the admin UI. The WHM/registrar APIs are GET-based, so a transport error
 * (e.g. a cURL timeout) can carry the full request URL — including a cPanel
 * password or API token — in its message. Everything that persists or displays
 * an error message runs it through here first.
 */
class Secrets
{
    /** Query-string / key=value parameters whose values must never be shown. */
    private const SENSITIVE_KEYS = [
        'password', 'pass', 'pwd', 'token', 'api.token', 'api_token',
        'apikey', 'api_key', 'key', 'secret', 'authorization', 'auth',
        // Porkbun uses these exact body keys for its API credentials.
        'secretapikey', 'secret_key', 'secretkey',
    ];

    public static function redact(?string $text): string
    {
        if ($text === null || $text === '') {
            return (string) $text;
        }

        $keys = implode('|', array_map('preg_quote', self::SENSITIVE_KEYS));

        // key=value (URL query strings, e.g. ...&password=secret&...)
        $text = preg_replace('/(\b(?:'.$keys.')=)[^&\s"\'<>]+/i', '$1[redacted]', $text);

        // "key": "value" (JSON / header form). The value class excludes & and =
        // so it never runs past a query-string boundary into a later parameter.
        $text = preg_replace('/(["\'](?:'.$keys.')["\']\s*:\s*["\']?)[^"\',}\s&=]+/i', '$1[redacted]', $text);

        return $text;
    }

    /**
     * Recursively redact a payload array: any value under a sensitive key is
     * masked, and any string value is run through the text redactor.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function redactArray(array $payload): array
    {
        array_walk_recursive($payload, function (&$value, $key) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $value = '[redacted]';

                return;
            }

            if (is_string($value)) {
                $value = self::redact($value);
            }
        });

        return $payload;
    }
}
