<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hosting accounts gain a customer-controllable auto-renew flag (domains
 * already have one). When off, the renewal engine reminds but does not treat
 * the service as auto-renewing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(true)->after('renewal_date');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn('auto_renew');
        });
    }
};
