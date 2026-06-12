<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Package → WHM Package Mapping
    |--------------------------------------------------------------------------
    |
    | Fallback mapping used by the CpanelPackageMapper when a hosting_packages
    | row does not specify an explicit whm_package_name. The authoritative
    | mapping lives in the hosting_packages table and is editable by Super
    | Admins; this config provides safe defaults.
    |
    */

    'default_package' => env('WHM_DEFAULT_PACKAGE', 'kwashqap_starter'),

    'package_map' => [
        'starter' => 'kwashqap_starter',
        'business' => 'kwashqap_Business',
        'pro' => 'kwashqap_Pro',
        'agency' => 'kwashqap_Agency Ecommerce',
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Defaults
    |--------------------------------------------------------------------------
    |
    | Feature flags passed to WHM createacct. Email authentication records
    | (SPF/DKIM/DMARC) are enabled at account creation so mail deliverability
    | is correct from day one.
    |
    */

    'account_defaults' => [
        'dkim' => 1,
        'spf' => 1,
        'dmarc' => 1,
        'cgi' => 1,
    ],

];
