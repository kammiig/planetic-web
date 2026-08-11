<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-currency pricing for the regional storefronts (config/regions.php).
 *
 * Domain selling prices get an explicit USD column rather than being converted
 * from GBP at runtime: an exchange-rate calculation produces prices like
 * "$16.44/yr", and the customer-facing figure is an admin pricing decision, not
 * an arithmetic result. A TLD with no USD price simply is not sold in the
 * international storefront.
 *
 * Catalogue products (website package, hosting plans) need no schema change —
 * product_prices already carries a `currency` column, so a second row per
 * product is all that is required. The unique index added here stops a product
 * ever acquiring two active prices for the same cycle AND currency, which would
 * make checkout's price lookup non-deterministic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tld_pricings', function (Blueprint $table) {
            $table->decimal('register_price_usd', 10, 2)->nullable()->after('register_price');
            $table->decimal('renew_price_usd', 10, 2)->nullable()->after('renew_price');
            $table->decimal('transfer_price_usd', 10, 2)->nullable()->after('transfer_price');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->unique(['product_id', 'billing_cycle', 'currency'], 'product_prices_product_cycle_currency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropUnique('product_prices_product_cycle_currency_unique');
        });

        Schema::table('tld_pricings', function (Blueprint $table) {
            $table->dropColumn(['register_price_usd', 'renew_price_usd', 'transfer_price_usd']);
        });
    }
};
