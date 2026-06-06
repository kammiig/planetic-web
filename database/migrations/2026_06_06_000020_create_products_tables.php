<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // ProductType
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('billing_cycle'); // one_time | monthly | yearly
            $table->string('currency', 3)->default('GBP');
            $table->decimal('amount', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('hosting_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('whm_package_name');
            $table->integer('disk_limit_mb')->nullable();
            $table->integer('bandwidth_limit_mb')->nullable();
            $table->integer('email_accounts_limit')->nullable();
            $table->integer('database_limit')->nullable();
            $table->integer('domain_limit')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_packages');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
    }
};
