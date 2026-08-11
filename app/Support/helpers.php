<?php

use App\Models\SiteSetting;
use App\Support\Copy;
use App\Support\Money;
use App\Support\Region;

if (! function_exists('money')) {
    /**
     * Format an amount for display. With no currency it follows the storefront
     * the visitor is browsing; pass the currency stored on an order, invoice or
     * payment to render that record faithfully wherever it appears.
     *
     * Every price on the site goes through this (or <x-money>) so a currency
     * symbol is never hardcoded in a template.
     */
    function money(float|int|string|null $amount, ?string $currency = null, bool $withCode = false): string
    {
        return Money::format($amount, $currency, $withCode);
    }
}

if (! function_exists('money_compact')) {
    /** money() without ".00" on whole amounts, for headline marketing copy. */
    function money_compact(float|int|string|null $amount, ?string $currency = null): string
    {
        return Money::compact($amount, $currency);
    }
}

if (! function_exists('region')) {
    /** The regional storefront serving the current request. */
    function region(): Region
    {
        return Region::current();
    }
}

if (! function_exists('setting')) {
    /**
     * Read an admin-editable site setting (type-cast), falling back to the
     * given default when the key is missing or blank. Cached on the hot path.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $value = SiteSetting::get($key, $default);

        // Admin copy may carry storefront tokens (":price", ":currency",
        // ":symbol") so one stored sentence renders correctly in every region.
        return is_string($value) ? Copy::localise($value) : $value;
    }
}
