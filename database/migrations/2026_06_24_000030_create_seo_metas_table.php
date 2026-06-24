<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-page SEO metadata, keyed by route name (e.g. "home", "hosting.index").
 * The public layout reads the row for the current route and falls back to
 * sensible defaults when no row exists, so SEO is fully admin-editable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_metas', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique();   // route name
            $table->string('label')->nullable();     // human label for the admin list
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->longText('schema_json')->nullable(); // optional extra JSON-LD
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metas');
    }
};
