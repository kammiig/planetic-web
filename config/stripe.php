<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Credentials
    |--------------------------------------------------------------------------
    |
    | These values are read exclusively from the environment. Secret keys must
    | never be hardcoded or exposed to the frontend. Only the public key is
    | safe to expose to the browser.
    |
    */

    'public_key' => env('STRIPE_PUBLIC_KEY'),

    'secret_key' => env('STRIPE_SECRET_KEY'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'gbp'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Pin the Stripe API version used by the SDK so webhook payload shapes are
    | predictable across deployments.
    |
    */

    'api_version' => env('STRIPE_API_VERSION', '2024-06-20'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Tolerance
    |--------------------------------------------------------------------------
    |
    | Maximum allowed difference (in seconds) between the timestamp on a Stripe
    | signature and the current time when verifying webhook authenticity.
    |
    */

    'webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),

];
