<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Synchronous Provisioning
    |--------------------------------------------------------------------------
    |
    | When true (the default), provisioning runs inline immediately after a
    | verified Stripe payment instead of being pushed onto the queue. This is
    | the reliable choice for cPanel shared hosting where a long-running queue
    | worker is not guaranteed: services are created the moment payment is
    | confirmed, with no dependency on a background worker.
    |
    | Set PROVISIONING_SYNC=false only if you run a dedicated queue worker
    | (e.g. a `queue:work` daemon or a per-minute `queue:work --stop-when-empty`
    | cron). See README "Queue & provisioning".
    |
    */

    'sync' => filter_var(env('PROVISIONING_SYNC', true), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Dry-run (safe test mode)
    |--------------------------------------------------------------------------
    |
    | When true, provisioning creates and activates the local service records
    | (domain, Cloudflare zone, DNS, hosting) WITHOUT calling NameSilo, WHM or
    | Cloudflare. Use this to exercise the full checkout → dashboard flow with
    | Stripe test keys only, before live registrar/WHM/Cloudflare credentials
    | are in place. Never enable in production once real provisioning is live.
    |
    */

    'dry_run' => filter_var(env('PROVISIONING_DRY_RUN', false), FILTER_VALIDATE_BOOL),

];
