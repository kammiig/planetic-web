<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare DNS API
    |--------------------------------------------------------------------------
    |
    | Use a scoped API token with access only to the required zone/DNS
    | permissions. Avoid the global API key. The token must never be exposed
    | to the frontend.
    |
    */

    'api_token' => env('CLOUDFLARE_API_TOKEN'),

    'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),

    'api_base' => env('CLOUDFLARE_API_BASE', 'https://api.cloudflare.com/client/v4'),

    'default_ssl_mode' => env('CLOUDFLARE_DEFAULT_SSL_MODE', 'full'),

    'always_use_https' => filter_var(env('CLOUDFLARE_ALWAYS_USE_HTTPS', true), FILTER_VALIDATE_BOOL),

    'proxied_website_records' => filter_var(env('CLOUDFLARE_PROXIED_WEBSITE_RECORDS', true), FILTER_VALIDATE_BOOL),

    'request_timeout' => (int) env('CLOUDFLARE_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Default DNS Record Settings
    |--------------------------------------------------------------------------
    |
    | TTL value of 1 means "automatic" in Cloudflare. The mail hostname and
    | DMARC policy are used when building the default DNS record set for a
    | newly provisioned domain.
    |
    */

    'dns_ttl' => (int) env('DEFAULT_DNS_TTL', 1),

    'mail_hostname' => env('DEFAULT_MAIL_SERVER_HOSTNAME', 'mail'),

    'dmarc_policy' => env('DEFAULT_DMARC_POLICY', 'none'),

    /*
    |--------------------------------------------------------------------------
    | Proxy Policy
    |--------------------------------------------------------------------------
    |
    | Only website records (@ and www) may be proxied through Cloudflare.
    | Mail, cPanel and service records must always remain DNS-only so that
    | email and control-panel access continue to function.
    |
    */

    'proxyable_names' => ['@', 'www'],

    'never_proxy_names' => ['mail', 'webmail', 'cpanel', 'whm', 'ftp', 'autodiscover'],

];
