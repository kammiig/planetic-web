<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database-driven content (CMS) so the marketing site can be edited from the
 * admin panel without touching code:
 *  - site_settings : key/value store for all editable copy, contact details,
 *                    social links, CTA labels and toggles (grouped for the UI).
 *  - faqs          : per-page question/answer pairs (also power FAQ schema).
 *  - testimonials  : customer reviews shown on the homepage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index(); // hero, contact, social, footer, cta, sections…
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('text'); // text|textarea|url|email|image|boolean|json
            $table->string('label')->nullable();
            $table->text('help')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('page')->default('home')->index(); // home|hosting|website-package|domains
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->string('company')->nullable();
            $table->text('body');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('site_settings');
    }
};
