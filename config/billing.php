<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The platform trades in GBP. All prices are calculated server-side and
    | stored as decimals; Stripe amounts are expressed in the smallest unit
    | (pence) at the integration boundary only.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'gbp'),

    /*
    |--------------------------------------------------------------------------
    | Renewal & Grace Period
    |--------------------------------------------------------------------------
    |
    | Grace period (in days) after a failed renewal payment before hosting is
    | suspended. Renewal reminder schedule is expressed as a list of days
    | before the renewal date on which a reminder should be sent.
    |
    */

    'grace_period_days' => (int) env('BILLING_GRACE_PERIOD_DAYS', 7),

    'renewal_reminder_days_before' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('RENEWAL_REMINDER_DAYS_BEFORE', '30,14,7,3,1'))
    ), fn ($d) => $d > 0)),

    /*
    |--------------------------------------------------------------------------
    | The £200 Bespoke Website Package
    |--------------------------------------------------------------------------
    |
    | Business rule: the website package includes a free domain and hosting for
    | the FIRST YEAR ONLY. Renewal applies after the first year. Never market
    | this as "free forever".
    |
    */

    'website_package' => [
        'price' => (float) env('WEBSITE_PACKAGE_PRICE', 200.00),
        'includes_free_first_year' => true,
        'free_year_notice' => 'Free domain and hosting for the first year. Renewal applies after the first year.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational Email Addresses
    |--------------------------------------------------------------------------
    */

    'admin_email' => env('ADMIN_EMAIL', 'admin@planeticweb.com'),

    'support_email' => env('SUPPORT_EMAIL', 'support@planeticweb.com'),

    'billing_email' => env('BILLING_EMAIL', 'billing@planeticweb.com'),

];
