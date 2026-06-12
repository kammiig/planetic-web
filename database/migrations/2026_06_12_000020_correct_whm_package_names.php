<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The WHM packages on the live server are prefixed with the reseller
     * account (kwashqap_*), not planetic_*. createacct rejects unknown
     * package names, so hosting provisioning failed. Data migration so the
     * fix reaches every environment on deploy; idempotent — only rows still
     * holding the old name are touched (admin edits are preserved).
     */
    private const RENAMES = [
        'planetic_starter' => 'kwashqap_starter',
        'planetic_business' => 'kwashqap_Business',
        'planetic_pro' => 'kwashqap_Pro',
        'planetic_agency' => 'kwashqap_Agency Ecommerce',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('hosting_packages')->where('whm_package_name', $old)->update(['whm_package_name' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('hosting_packages')->where('whm_package_name', $new)->update(['whm_package_name' => $old]);
        }
    }
};
