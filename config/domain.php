<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Registrar
    |--------------------------------------------------------------------------
    |
    | Selects which registrar implementation the RegistrarInterface binding
    | resolves to. Porkbun is the primary/default registrar (cheapest);
    | NameSilo and Namecheap remain optional fallbacks, used only when
    | DEFAULT_REGISTRAR names them. Swappable purely through configuration —
    | no code changes required.
    |
    | DEFAULT_REGISTRAR is the canonical variable; DOMAIN_REGISTRAR is honoured
    | as a legacy alias so existing deployments keep working.
    |
    */

    'default_registrar' => env('DEFAULT_REGISTRAR', env('DOMAIN_REGISTRAR', 'porkbun')),

    /*
    |--------------------------------------------------------------------------
    | Porkbun (Primary / Default)
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
