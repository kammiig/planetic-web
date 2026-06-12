<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A hosting account must be visible in the dashboard the moment it is
        // paid for — even when the customer chose "I'll provide my domain
        // later" (website package). Domain and username are filled in when the
        // domain is provided, so both become nullable.
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->string('domain_name')->nullable()->change();
            $table->string('whm_username')->nullable()->change();
        });

        // Some hosting plans bundle a free first-year domain (admin-managed).
        Schema::table('hosting_packages', function (Blueprint $table) {
            $table->boolean('includes_free_domain')->default(false)->after('domain_limit');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_packages', function (Blueprint $table) {
            $table->dropColumn('includes_free_domain');
        });

        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->string('domain_name')->nullable(false)->change();
            $table->string('whm_username')->nullable(false)->change();
        });
    }
};
