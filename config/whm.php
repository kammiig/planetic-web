<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Namecheap WHM / cPanel Reseller API
    |--------------------------------------------------------------------------
    |
    | Credentials for the WHM server that provisions customer cPanel accounts.
    | Use a scoped WHM API token (never the root password). All WHM calls must
    | run from the backend only — the token must never reach the frontend.
    |
    */

    'host' => env('WHM_HOST'),

    'port' => (int) env('WHM_PORT', 2087),

    'username' => env('WHM_USERNAME'),

    'token' => env('WHM_API_TOKEN'),

    'verify_ssl' => filter_var(env('WHM_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),

    'default_package' => env('WHM_DEFAULT_PACKAGE', 'kwashqap_starter'),

    'server_ip' => env('WHM_SERVER_IP'),

    'server_hostname' => env('WHM_SERVER_HOSTNAME'),

    'cpanel_login_url' => env('WHM_CPANEL_LOGIN_URL'),

    /*
    |--------------------------------------------------------------------------
    | Account Generation
    |--------------------------------------------------------------------------
    |
    | Controls how cPanel usernames are generated. WHM usernames must be
    | lowercase, alphanumeric and 8 characters or fewer on most cPanel builds.
    |
    */

    'username_prefix' => env('WHM_USERNAME_PREFIX', 'pw'),

    'request_timeout' => (int) env('WHM_REQUEST_TIMEOUT', 30),

];
