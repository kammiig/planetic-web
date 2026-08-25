<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Unlisted" products: active and fully purchasable, but withheld from the
 * public pricing tables.
 *
 * This is deliberately separate from is_active. Deactivating a product also
 * stops it being bought — which is exactly what we do NOT want for a plan that
 * is offered privately by link. is_hidden only removes it from the storefront's
 * listings; the catalogue, the cart and checkout treat it like any other plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->index()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};
