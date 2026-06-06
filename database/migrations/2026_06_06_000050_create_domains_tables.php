<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('domain_name')->unique();
            $table->string('sld');
            $table->string('tld');
            $table->string('registrar')->nullable();
            $table->string('registrar_domain_id')->nullable();
            $table->string('registrar_order_id')->nullable();
            $table->string('status')->index(); // DomainStatus
            $table->date('registration_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->boolean('whois_privacy')->default(true);
            $table->boolean('registrar_lock')->default(true);
            // Cyclic reference to cloudflare_zones — kept as an indexed column
            // (no DB-level FK) because cloudflare_zones references domains.
            $table->unsignedBigInteger('cloudflare_zone_id')->nullable()->index();
            $table->json('nameservers')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('domain_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('contact_type'); // registrant | admin | technical | billing
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postcode');
            $table->string('country', 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_contacts');
        Schema::dropIfExists('domains');
    }
};
