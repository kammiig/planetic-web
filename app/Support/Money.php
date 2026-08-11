<?php

namespace App\Support;

/**
 * Formats money for display. Every price on the site goes through here so that
 * the currency symbol is never hardcoded in a view again — the region decides
 * it (see App\Support\Region).
 *
 * Amounts are always plain decimals in the currency's major unit; conversion to
 * Stripe's minor units happens only at the integration boundary.
 */
class Money
{
    /**
     * Format an amount for a currency. Falls back to the current region's
     * currency when none is given, which is what nearly every view wants.
     *
     * Currency is displayed by symbol only when it is unambiguous in context.
     * USD is shown as "$" inside the international storefront but the symbol is
     * the same one Canada, Australia and others use, so a currency code is
     * appended whenever the amount is shown outside its own region's tree
     * (invoices, emails and the customer portal, which are region-agnostic).
     */
    public static function format(float|int|string|null $amount, ?string $currency = null, bool $withCode = false): string
    {
        $currency = strtoupper($currency ?: Region::current()->currency());
        $value = number_format((float) $amount, 2);
        $symbol = self::symbol($currency);

        return $symbol.$value.($withCode ? ' '.$currency : '');
    }

    /**
     * Format without decimals when the amount is whole — for marketing copy
     * ("£200") rather than ledgers ("£200.00").
     */
    public static function compact(float|int|string|null $amount, ?string $currency = null): string
    {
        $value = (float) $amount;

        if (fmod($value, 1.0) !== 0.0) {
            return self::format($value, $currency);
        }

        return self::symbol(strtoupper($currency ?: Region::current()->currency())).number_format($value, 0);
    }

    /** The display symbol for a currency code. */
    public static function symbol(string $currency): string
    {
        foreach (Region::all() as $region) {
            if ($region->currency() === strtoupper($currency)) {
                return $region->symbol();
            }
        }

        return match (strtoupper($currency)) {
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€',
            default => strtoupper($currency).' ',
        };
    }

    /** Convert a major-unit decimal to the minor units Stripe charges in. */
    public static function toMinorUnits(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
