<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cloudflare_zone_id')->constrained()->cascadeOnDelete();
            $table->string('cloudflare_record_id')->nullable()->index();
            $table->string('type'); // A | AAAA | CNAME | MX | TXT | NS | SRV | CAA
            $table->string('name');
            $table->text('content');
            $table->integer('ttl')->nullable();
            $table->boolean('proxied')->default(false);
            $table->integer('priority')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
