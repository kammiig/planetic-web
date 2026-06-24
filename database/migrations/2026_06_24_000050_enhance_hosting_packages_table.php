<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds admin-editable display fields to hosting packages so a plan can be fully
 * managed from one screen: a marketing tagline, a custom feature list, an SSL
 * inclusion flag, a "popular/recommended" flag and an explicit display order.
 * Pricing continues to live in product_prices (the checkout source of truth).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_packages', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->json('features')->nullable()->after('domain_limit');
            $table->boolean('ssl_included')->default(true)->after('features');
            $table->boolean('is_popular')->default(false)->after('ssl_included');
            $table->integer('sort_order')->default(0)->index()->after('is_popular');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_packages', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'features', 'ssl_included', 'is_popular', 'sort_order']);
        });
    }
};
