<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed domain (TLD) pricing. The customer-facing selling price for a
 * domain is resolved from this table (longest-matching TLD), replacing the old
 * single flat catalogue price. cost_price/markup are admin-only reference
 * figures (e.g. synced from Porkbun) and are never shown to customers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tld_pricings', function (Blueprint $table) {
            $table->id();
            $table->string('tld')->unique();              // stored without a leading dot, e.g. "com", "co.uk"
            $table->decimal('register_price', 10, 2);     // customer selling price / year (GBP)
            $table->decimal('renew_price', 10, 2)->nullable();
            $table->decimal('transfer_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();   // admin reference (e.g. Porkbun cost, converted)
            $table->decimal('markup', 10, 2)->nullable();       // admin reference margin
            $table->boolean('free_eligible')->default(true);    // can be the free first-year domain
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamp('cost_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tld_pricings');
    }
};
