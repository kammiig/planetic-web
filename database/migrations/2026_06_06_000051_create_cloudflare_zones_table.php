<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cloudflare_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('zone_id')->unique();
            $table->string('zone_name');
            $table->string('status')->index(); // CloudflareZone status
            $table->json('name_servers')->nullable();
            $table->string('ssl_mode')->nullable();
            $table->boolean('always_use_https')->default(true);
            $table->timestamp('created_on_cloudflare_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloudflare_zones');
    }
};
