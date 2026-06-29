<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds review provenance to testimonials so the homepage can show trustworthy,
 * source-attributed reviews:
 *  - source      : manual | trustpilot | google (controls which logo/label shows)
 *  - source_url  : optional link to the original review on Trustpilot/Google
 *  - is_verified : admin confirms this is a genuine verified review (drives the
 *                  "Verified …" wording). Branding is NEVER faked — it only
 *                  appears when the admin selects that source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('rating')->index();
            $table->string('source_url')->nullable()->after('source');
            $table->boolean('is_verified')->default(false)->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn(['source', 'source_url', 'is_verified']);
        });
    }
};
