<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores Cloudflare's Universal SSL certificate status alongside the zone so
 * the customer dashboard can show an accurate SSL state (Active / pending)
 * rather than always deriving it from the zone's nameserver-verification state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloudflare_zones', function (Blueprint $table) {
            $table->string('ssl_status')->nullable()->after('ssl_mode');
        });
    }

    public function down(): void
    {
        Schema::table('cloudflare_zones', function (Blueprint $table) {
            $table->dropColumn('ssl_status');
        });
    }
};
