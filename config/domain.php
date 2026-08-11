<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Registrar
    |--------------------------------------------------------------------------
    |
    | Selects which registrar implementation the RegistrarInterface binding
    | resolves to. Cloudflare is the primary/default registrar (at-cost pricing,
    | and its domains are locked to Cloudflare nameservers — which is where this
    | platform points every domain anyway). Porkbun, NameSilo and Namecheap
    | remain available. Swappable purely through configuration — no code
    | changes required.
    |
    | DEFAULT_REGISTRAR is the canonical variable; DOMAIN_REGISTRAR is honoured
    | as a legacy alias so existing deployments keep working.
    |
    */

    'default_registrar' => env('DEFAULT_REGISTRAR', env('DOMAIN_REGISTRAR', 'cloudflare')),

    /*
    |--------------------------------------------------------------------------
    | Fallback Registrar
    |--------------------------------------------------------------------------
    |
    | The Cloudflare Registrar API is in beta and covers only a subset of the
    | extensions Cloudflare supports in its dashboard — .uk is Cloudflare's own
    | documented example of one it cannot yet register through the API. Because
    | .co.uk is this platform's primary TLD, such domains are routed to this
    | fallback registrar automatically rather than failing a paid order.
    |
    | ONLY provider-capability failures fall back. A taken domain, a rejected
    | contact, a declined payment or a bad token all fail fast on the primary,
    | so the fallback can never mask a real error or double-buy a domain.
    |
    | Set FALLBACK_REGISTRAR=none to disable fallback routing entirely.
    |
    */

    'fallback_registrar' => env('FALLBACK_REGISTRAR', 'porkbun'),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Registrar (Primary / Default)
    |--------------------------------------------------------------------------
    |
    | Registrar API beta. Uses a scoped bearer token — separate from the DNS
    | token, because registration is a spending permission — read only from the
    | server environment and never exposed to the frontend, logs, emails or
    | admin screens. Falls back to the DNS token/base when unset so a single
    | correctly-scoped token also works.
    |
    | Registrations are charged to the Cloudflare account's default payment
    | method and are NON-REFUNDABLE, so registerDomain() is idempotent: it
    | adopts an existing registration rather than buying the domain twice.
    |
    */

    'cloudflare' => [
        'enabled' => filter_var(env('CLOUDFLARE_REGISTRAR_ENABLED', true), FILTER_VALIDATE_BOOL),
        'api_token' => env('CLOUDFLARE_REGISTRAR_API_TOKEN', env('CLOUDFLARE_API_TOKEN')),
        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),
        'endpoint' => env('CLOUDFLARE_API_BASE', 'https://api.cloudflare.com/client/v4'),

        // Registration can complete asynchronously (HTTP 202). How many times to
        // poll registration-status, and how long to wait between polls, before
        // handing the step back for retry.
        'registration_poll_attempts' => (int) env('CLOUDFLARE_REGISTRATION_POLL_ATTEMPTS', 5),
        'registration_poll_seconds' => (int) env('CLOUDFLARE_REGISTRATION_POLL_SECONDS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Porkbun (Fallback)
    |--------------------------------------------------------------------------
    |
    | API v3. Credentials are read only from the server environment and are
    | never exposed to the frontend, logs, emails or admin screens.
    |
    */

    'porkbun' => [
        'enabled' => filter_var(env('PORKBUN_ENABLED', true), FILTER_VALIDATE_BOOL),
        'api_key' => env('PORKBUN_API_KEY'),
        'secret_key' => env('PORKBUN_SECRET_KEY'),
        'endpoint' => env('PORKBUN_API_ENDPOINT', 'https://api.porkbun.com/api/json/v3'),
    ],

    /*
    |--------------------------------------------------------------------------
    | NameSilo (Optional fallback)
    |--------------------------------------------------------------------------
    */

    'namesilo' => [
        'enabled' => filter_var(env('NAMESILO_ENABLED', true), FILTER_VALIDATE_BOOL),
        'api_key' => env('NAMESILO_API_KEY'),
        'endpoint' => env('NAMESILO_API_ENDPOINT', 'https://www.namesilo.com/api'),
        'sandbox' => filter_var(env('NAMESILO_SANDBOX', false), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namecheap (Optional fallback)
    |--------------------------------------------------------------------------
    */

    'namecheap' => [
        'enabled' => filter_var(env('NAMECHEAP_ENABLED', true), FILTER_VALIDATE_BOOL),
        'api_user' => env('NAMECHEAP_API_USER'),
        'api_key' => env('NAMECHEAP_API_KEY'),
        'username' => env('NAMECHEAP_USERNAME'),
        'client_ip' => env('NAMECHEAP_CLIENT_IP'),
        'endpoint' => env('NAMECHEAP_API_ENDPOINT', 'https://api.namecheap.com/xml.response'),
        'sandbox' => filter_var(env('NAMECHEAP_SANDBOX', false), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults applied when registering a domain. WHOIS privacy and auto-renew
    | are enabled by default; the registration term is one year (the free
    | first-year term included with the website package). Note: the Porkbun
    | API always registers for one year, which matches this default.
    |
    */

    'defaults' => [
        'years' => 1,
        'whois_privacy' => true,
        'auto_renew' => true,
        'registrar_lock' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Suggested TLDs
    |--------------------------------------------------------------------------
    |
    | Alternative TLDs offered as suggestions when a searched domain is taken.
    |
    */

    'suggestion_tlds' => ['co.uk', 'com', 'net', 'org', 'io', 'uk', 'co', 'shop', 'online', 'store'],

    'request_timeout' => (int) env('DOMAIN_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Porkbun checkDomain rate-limit retry
    |--------------------------------------------------------------------------
    |
    | Porkbun's /domain/checkDomain endpoint is rate-limited (≈1 request / 10s).
    | When a registration's availability/price check is rate-limited, the
    | registrar waits this many seconds (capped) and re-checks once to get the
    | exact quote Porkbun's create call requires. Set to 0 to disable the wait.
    |
    */

    'rate_limit_retry_seconds' => (int) env('PORKBUN_RATE_LIMIT_RETRY_SECONDS', 11),

    /*
    |--------------------------------------------------------------------------
    | USD → GBP conversion (admin reference only)
    |--------------------------------------------------------------------------
    |
    | Registrars (e.g. Porkbun) quote wholesale prices in USD. When an admin
    | syncs cost prices into the TLD price book this factor converts them to
    | GBP for the internal cost_price/markup reference figures. It never
    | affects the customer-facing selling price, which is the admin-set
    | register_price in GBP.
    |
    */

    'usd_to_gbp' => (float) env('DOMAIN_USD_TO_GBP', 0.79),

];
