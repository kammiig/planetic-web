<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Registrar
    |--------------------------------------------------------------------------
    |
    | Selects which registrar implementation the RegistrarInterface binding
    | resolves to. NameSilo is the primary registrar; Namecheap is the backup.
    | Swappable purely through configuration — no code changes required.
    |
    */

    'default_registrar' => env('DOMAIN_REGISTRAR', 'namesilo'),

    /*
    |--------------------------------------------------------------------------
    | NameSilo (Primary)
    |--------------------------------------------------------------------------
    */

    'namesilo' => [
        'api_key' => env('NAMESILO_API_KEY'),
        'endpoint' => env('NAMESILO_API_ENDPOINT', 'https://www.namesilo.com/api'),
        'sandbox' => filter_var(env('NAMESILO_SANDBOX', false), FILTER_VALIDATE_BOOL),
    ],

    /*
    |--------------------------------------------------------------------------
    | Namecheap (Backup)
    |--------------------------------------------------------------------------
    */

    'namecheap' => [
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
    | first-year term included with the website package).
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

    'suggestion_tlds' => ['co.uk', 'com', 'net', 'org', 'io', 'uk'],

    'request_timeout' => (int) env('DOMAIN_REQUEST_TIMEOUT', 30),

];
