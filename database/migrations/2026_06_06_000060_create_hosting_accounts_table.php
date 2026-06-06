<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hosting_package_id')->constrained();
            $table->string('domain_name');
            $table->string('whm_username')->unique();
            $table->string('whm_account_id')->nullable();
            $table->string('server_hostname')->nullable();
            $table->string('server_ip')->nullable();
            $table->string('cpanel_url')->nullable();
            $table->string('status')->index(); // HostingStatus
            $table->integer('disk_limit_mb')->nullable();
            $table->integer('bandwidth_limit_mb')->nullable();
            $table->timestamp('created_on_whm_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->date('renewal_date')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_accounts');
    }
};
