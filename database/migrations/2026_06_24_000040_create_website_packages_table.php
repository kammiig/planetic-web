<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable detail for bespoke website-development packages, mirroring how
 * hosting_packages extends a hosting Product. The price stays in product_prices
 * (one_time) so the existing cart/checkout/Stripe pipeline is untouched; this
 * table holds the admin-editable marketing content, feature list, project
 * intake questions and inclusion flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();          // string[]
            $table->json('intake_questions')->nullable();  // [{label, type, required}]
            $table->boolean('includes_free_domain')->default(true);
            $table->boolean('includes_hosting')->default(true);
            $table->foreignId('hosting_package_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_packages');
    }
};
